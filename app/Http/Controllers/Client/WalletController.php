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

    public function initialize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user = auth()->user();
        $reference = 'WT-' . strtoupper(uniqid()) . '-' . $user->id;

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $data['amount'],
            'status' => 'pending',
            'reference' => $reference,
            'description' => 'Wallet top-up via Paystack',
        ]);

        $client = new HttpClient();

        try {
            $response = $client->post('https://api.paystack.co/transaction/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $user->email,
                    'amount' => (int) round($data['amount'] * 100),
                    'currency' => 'KES',
                    'reference' => $reference,
                    'callback_url' => route('account.wallet.callback'),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (empty($body['status']) || empty($body['data']['authorization_url'])) {
                throw new \RuntimeException('Paystack did not return an authorization URL.');
            }

            return response()->json([
                'authorization_url' => $body['data']['authorization_url'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Paystack initialize failed: ' . $exception->getMessage());
            $transaction->update(['status' => 'failed']);

            return response()->json([
                'error' => 'Unable to start payment. Please try again shortly.',
            ], 500);
        }
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
            } else {
                $transaction->update(['status' => 'failed']);
            }
        } catch (\Throwable $exception) {
            Log::error('Paystack verify failed: ' . $exception->getMessage());
        }
    }
}
