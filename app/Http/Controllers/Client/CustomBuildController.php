<?php

namespace Pterodactyl\Http\Controllers\Client;

use Log;
use Illuminate\Http\Request;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Transaction;
use Pterodactyl\Models\ResourcePrice;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Servers\ServerCreationService;

class CustomBuildController extends Controller
{
    public function __construct(protected ServerCreationService $creationService)
    {
    }

    public function options(): JsonResponse
    {
        $eggs = Egg::with('nest')
            ->orderBy('nest_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($egg) => $egg->nest->name ?? 'Uncategorized')
            ->map(fn ($group) => $group->map(fn ($egg) => ['id' => $egg->id, 'name' => $egg->name]));

        $prices = ResourcePrice::where('is_active', true)
            ->whereNotNull('resource_key')
            ->get()
            ->keyBy('resource_key')
            ->map(fn ($item) => [
                'price_kes' => (float) $item->price_kes,
                'unit_label' => $item->unit_label,
            ]);

        $cap = (float) (Plan::where('is_active', true)->max('price') ?? 0);

        return response()->json([
            'eggs' => $eggs,
            'prices' => $prices,
            'price_cap' => $cap,
        ]);
    }

    protected function calculatePrice(array $data, $prices): float
    {
        $total = 0;
        $total += ($data['memory'] / 1024) * ($prices['ram']['price_kes'] ?? 0);
        $total += ($data['disk'] / 1024) * ($prices['disk']['price_kes'] ?? 0);
        $total += ($data['cpu'] / 100) * ($prices['cpu']['price_kes'] ?? 0);
        $total += $data['databases'] * ($prices['database']['price_kes'] ?? 0);
        $total += $data['backups'] * ($prices['backup']['price_kes'] ?? 0);
        $total += $data['allocations'] * ($prices['allocation']['price_kes'] ?? 0);

        return round($total, 2);
    }

    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'egg_id' => 'required|integer|exists:eggs,id',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'cpu' => 'required|integer|min:0',
            'databases' => 'required|integer|min:0',
            'backups' => 'required|integer|min:0',
            'allocations' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        $prices = ResourcePrice::where('is_active', true)
            ->whereNotNull('resource_key')
            ->get()
            ->keyBy('resource_key');

        $price = $this->calculatePrice($data, $prices);
        $cap = (float) (Plan::where('is_active', true)->max('price') ?? 0);

        if ($cap > 0 && $price > $cap) {
            return response()->json([
                'error' => 'This configuration exceeds the maximum allowed price. Please reduce your selection.',
            ], 422);
        }

        if ((float) $user->wallet_balance < $price) {
            return response()->json(['error' => 'Insufficient wallet balance. Please top up first.'], 422);
        }

        $egg = Egg::query()->findOrFail($data['egg_id']);
        $dockerImage = is_array($egg->docker_images) && count($egg->docker_images) > 0
            ? array_values($egg->docker_images)[0]
            : null;

        $environment = [];
        foreach ($egg->variables as $variable) {
            $environment[$variable->env_variable] = $variable->default_value;
        }

        $locationIds = Location::query()->pluck('id')->toArray();

        if (empty($locationIds)) {
            return response()->json(['error' => 'No server locations are configured yet.'], 422);
        }

        $deployment = (new DeploymentObject())->setLocations($locationIds)->setDedicated(false);

        try {
            $server = $this->creationService->handle([
                'name' => $user->username . "'s Custom Server",
                'description' => 'Custom-built server (' . $data['memory'] . 'MB / ' . $data['disk'] . 'MB / ' . $data['cpu'] . '%).',
                'owner_id' => $user->id,
                'memory' => $data['memory'],
                'swap' => 0,
                'disk' => $data['disk'],
                'io' => 500,
                'cpu' => $data['cpu'],
                'database_limit' => $data['databases'],
                'allocation_limit' => $data['allocations'],
                'backup_limit' => $data['backups'],
                'egg_id' => $egg->id,
                'nest_id' => $egg->nest_id,
                'startup' => $egg->startup,
                'image' => $dockerImage,
                'environment' => $environment,
                'start_on_completion' => true,
            ], $deployment);
        } catch (\Throwable $exception) {
            Log::error('Custom build server creation failed: ' . $exception->getMessage());

            return response()->json([
                'error' => 'Unable to provision a server right now. No charge was made — please try again shortly or contact support.',
            ], 500);
        }

        \DB::transaction(function () use ($user, $price, $server) {
            $user->decrement('wallet_balance', $price);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'charge',
                'amount' => $price,
                'status' => 'success',
                'description' => 'Custom build (server #' . $server->id . ')',
            ]);
        });

        return response()->json([
            'server_id' => $server->id,
            'message' => 'Server created successfully.',
        ]);
    }
}
