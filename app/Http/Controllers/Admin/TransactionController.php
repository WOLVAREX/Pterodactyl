<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Transaction;
use Pterodactyl\Http\Controllers\Controller;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaction::query()->with('user')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->paginate(50)->appends($request->query());

        $stats = [
            'total_success' => (float) Transaction::where('status', 'success')->sum('amount'),
            'success_count' => (int) Transaction::where('status', 'success')->count(),
            'pending_count' => (int) Transaction::where('status', 'pending')->count(),
            'failed_count' => (int) Transaction::where('status', 'failed')->count(),
        ];

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search,
            ],
        ]);
    }
}
