<?php

namespace Pterodactyl\Http\Controllers\Client;

use Log;
use Illuminate\Http\Request;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\ResourcePrice;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Servers\ServerCreationService;

class PlanPurchaseController extends Controller
{
    public function __construct(protected ServerCreationService $creationService)
    {
    }

    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->whereNotNull('egg_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($plans);
    }

    protected function calculateAddonCost(array $addons, $prices): float
    {
        $total = 0;
        $total += ($addons['extra_memory'] / 1024) * ($prices['ram']['price_kes'] ?? 0);
        $total += ($addons['extra_disk'] / 1024) * ($prices['disk']['price_kes'] ?? 0);
        $total += ($addons['extra_cpu'] / 100) * ($prices['cpu']['price_kes'] ?? 0);
        $total += $addons['extra_databases'] * ($prices['database']['price_kes'] ?? 0);
        $total += $addons['extra_backups'] * ($prices['backup']['price_kes'] ?? 0);
        $total += $addons['extra_allocations'] * ($prices['allocation']['price_kes'] ?? 0);

        return round($total, 2);
    }

    public function purchase(Request $request, Plan $plan): JsonResponse
    {
        $addons = $request->validate([
            'extra_memory' => 'nullable|integer|min:0',
            'extra_disk' => 'nullable|integer|min:0',
            'extra_cpu' => 'nullable|integer|min:0',
            'extra_databases' => 'nullable|integer|min:0',
            'extra_backups' => 'nullable|integer|min:0',
            'extra_allocations' => 'nullable|integer|min:0',
        ]);

        $addons = array_merge([
            'extra_memory' => 0,
            'extra_disk' => 0,
            'extra_cpu' => 0,
            'extra_databases' => 0,
            'extra_backups' => 0,
            'extra_allocations' => 0,
        ], array_filter($addons, fn ($v) => $v !== null));

        $user = auth()->user();

        if (!$plan->is_active || !$plan->egg_id) {
            return response()->json(['error' => 'This plan is not currently available.'], 422);
        }

        $prices = ResourcePrice::where('is_active', true)
            ->whereNotNull('resource_key')
            ->get()
            ->keyBy('resource_key');

        $addonCost = $this->calculateAddonCost($addons, $prices);
        $totalPrice = round((float) $plan->price + $addonCost, 2);

        $cap = (float) (Plan::where('is_active', true)->max('price') ?? 0);
        if ($cap > 0 && $totalPrice > $cap) {
            return response()->json([
                'error' => 'This configuration (plan + add-ons) exceeds the maximum allowed price. Please reduce your add-ons.',
            ], 422);
        }

        if ((float) $user->wallet_balance < $totalPrice) {
            return response()->json(['error' => 'Insufficient wallet balance. Please top up first.'], 422);
        }

        $egg = \Pterodactyl\Models\Egg::query()->findOrFail($plan->egg_id);
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
                'name' => $user->username . "'s " . $plan->name,
                'description' => 'Provisioned via ' . $plan->name . ' plan purchase.',
                'owner_id' => $user->id,
                'memory' => $plan->memory + $addons['extra_memory'],
                'swap' => 0,
                'disk' => $plan->disk + $addons['extra_disk'],
                'io' => 500,
                'cpu' => $plan->cpu + $addons['extra_cpu'],
                'database_limit' => $plan->databases + $addons['extra_databases'],
                'allocation_limit' => $plan->allocations + $addons['extra_allocations'],
                'backup_limit' => $plan->backups + $addons['extra_backups'],
                'egg_id' => $plan->egg_id,
                'nest_id' => $plan->nest_id ?: $egg->nest_id,
                'startup' => $egg->startup,
                'image' => $dockerImage,
                'environment' => $environment,
                'start_on_completion' => true,
            ], $deployment);
        } catch (\Throwable $exception) {
            Log::error('Plan purchase server creation failed: ' . $exception->getMessage());

            return response()->json([
                'error' => 'Unable to provision a server right now. No charge was made — please try again shortly or contact support.',
            ], 500);
        }

        \DB::transaction(function () use ($user, $plan, $server, $totalPrice, $addonCost) {
            $user->decrement('wallet_balance', $totalPrice);

            $description = 'Purchased plan: ' . $plan->name . ' (server #' . $server->id . ')';
            if ($addonCost > 0) {
                $description .= ' + add-ons (KES ' . number_format($addonCost, 2) . ')';
            }

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'charge',
                'amount' => $totalPrice,
                'status' => 'success',
                'description' => $description,
            ]);
        });

        return response()->json([
            'server_id' => $server->id,
            'message' => 'Server created successfully.',
        ]);
    }
}
