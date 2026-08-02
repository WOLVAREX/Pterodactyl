<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\View\View;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Transaction;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    /**
     * BaseController constructor.
     */
    public function __construct(private SoftwareVersionService $version)
    {
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        $totalRevenue = Transaction::where('type', 'deposit')
            ->where('status', 'success')
            ->sum('amount');

        // New user signups per day for the last 14 days (including today),
        // zero-filled so the chart has no gaps.
        $signupsByDay = User::where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->get()
            ->groupBy(fn ($user) => $user->created_at->format('Y-m-d'))
            ->map->count();

        $signupLabels = [];
        $signupData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->format('Y-m-d');
            $signupLabels[] = $date->format('M j');
            $signupData[] = $signupsByDay->get($key, 0);
        }

        $nodeDistribution = Node::withCount('servers')
            ->orderByDesc('servers_count')
            ->get()
            ->map(fn ($node) => [
                'name' => $node->name,
                'count' => $node->servers_count,
            ])
            ->values();

        return view('admin.index', [
            'version' => $this->version,
            'totalServers' => Server::count(),
            'totalUsers' => User::count(),
            'totalNodes' => Node::count(),
            'totalRevenue' => $totalRevenue,
            'signupLabels' => $signupLabels,
            'signupData' => $signupData,
            'nodeDistribution' => $nodeDistribution,
        ]);
    }
}
