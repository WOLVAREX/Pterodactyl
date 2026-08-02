<?php

namespace Pterodactyl\Http\Controllers\Client;

use Log;
use Illuminate\View\View;
use Illuminate\Http\Request;
use GuzzleHttp\Client as HttpClient;
use Pterodactyl\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;

class WalletController extends Controller
{
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->publicKey = (string) config('services.paystack.public_key');
    }

    public function index(): View
    {
        return view('templates/base.core');
    }

    public function data(): JsonResponse
    {
        $user = auth()->user();

        $transactions = Transaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'balance' => (float) $user->wallet_balance,
            'transactions' => $transactions,
        ]);
    }

    public function initializeCard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user = auth()->user();
        $reference = 'WT-' . strtoupper(uniqid()) . '-' . $user->id;

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $data['amount'],
            'status' => 'pending',
            'reference' => $reference,
            'description' => 'Wallet top-up via card',
        ]);

        return response()->json([
            'public_key' => $this->publicKey,
            'reference' => $reference,
            'email' => $user->email,
            'amount' => (int) round($data['amount'] * 100),
        ]);
    }

    public function initializeMobileMoney(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10',
            'phone' => 'required|string',
        ]);

        $user = auth()->user();
        $reference = 'WT-' . strtoupper(uniqid()) . '-' . $user->id;
        $phone = $this->normalizePhone($data['phone']);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $data['amount'],
            'status' => 'pending',
            'reference' => $reference,
            'description' => 'Wallet top-up via M-Pesa STK push',
        ]);

        $client = new HttpClient();

        try {
            $response = $client->post('https://api.paystack.co/charge', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $user->email,
                    'amount' => (int) round($data['amount'] * 100),
                    'currency' => 'KES',
                    'reference' => $reference,
                    'mobile_money' => [
                        'phone' => $phone,
                        'provider' => 'mpesa',
                    ],
                ],
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode >= 400 || empty($body['status'])) {
                Log::error('Paystack mobile money charge failed', [
                    'status_code' => $statusCode,
                    'body' => $body,
                    'phone_sent' => $phone,
                ]);

                $transaction->update(['status' => 'failed']);

                return response()->json([
                    'error' => $body['message'] ?? 'Unable to start the M-Pesa payment. Please check the number and try again.',
                ], 500);
            }

            return response()->json([
                'reference' => $reference,
                'message' => $body['data']['display_text']
                    ?? 'Enter your M-Pesa PIN on your phone to complete this payment.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Paystack mobile money charge failed: ' . $exception->getMessage());
            $transaction->update(['status' => 'failed']);

            return response()->json([
                'error' => 'Unable to start the M-Pesa payment. Please check the number and try again.',
            ], 500);
        }
    }

    public function status(string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', auth()->id())
            ->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found.'], 404);
        }

        if ($transaction->status === 'pending') {
            $this->verifyAndCredit($reference);
            $transaction->refresh();
        }

        return response()->json([
            'status' => $transaction->status,
            'balance' => (float) auth()->user()->wallet_balance,
        ]);
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '254')) {
            $national = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $national = $digits;
        } else {
            $national = $digits;
        }

        // Paystack's M-Pesa charge docs specifically ask for the "+" country code
        // prefix, e.g. 0722000000 -> +254722000000 (not just 254722000000).
        return '+254' . $national;
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if ($reference) {
            $this->verifyAndCredit($reference);
        }

        return redirect('/account/wallet');
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Paystack-Signature');
        $payload = $request->getContent();

        $expected = hash_hmac('sha512', $payload, $this->secretKey);

        if (!$signature || !hash_equals($expected, $signature)) {
            Log::warning('Paystack webhook signature mismatch.');

            return response()->json(['status' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? null) === 'charge.success') {
            $reference = $event['data']['reference'] ?? null;

            if ($reference) {
                $this->verifyAndCredit($reference);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function verifyAndCredit(string $reference): void
    {
        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction || $transaction->status === 'success') {
            return;
        }

        $client = new HttpClient();

        try {
            $response = $client->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $status = $body['data']['status'] ?? null;
            $paidAmount = isset($body['data']['amount']) ? ((float) $body['data']['amount']) / 100 : null;

            if ($status === 'success' && $paidAmount !== null && abs($paidAmount - (float) $transaction->amount) < 0.01) {
                \DB::transaction(function () use ($transaction) {
                    $transaction->update(['status' => 'success']);

                    $transaction->user()->increment('wallet_balance', $transaction->amount);
                });
            } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $transaction->update(['status' => 'failed']);
            }
        } catch (\Throwable $exception) {
            Log::error('Paystack verify failed: ' . $exception->getMessage());
        }
    }
}
