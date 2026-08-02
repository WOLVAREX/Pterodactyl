<?php

namespace Pterodactyl\Http\Controllers\Client;

use Log;
use Pterodactyl\Models\Plan;
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

    public function purchase(Plan $plan): JsonResponse
    {
        $user = auth()->user();

        if (!$plan->is_active || !$plan->egg_id) {
            return response()->json(['error' => 'This plan is not currently available.'], 422);
        }

        if ((float) $user->wallet_balance < (float) $plan->price) {
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
                'memory' => $plan->memory,
                'swap' => 0,
                'disk' => $plan->disk,
                'io' => 500,
                'cpu' => $plan->cpu,
                'database_limit' => $plan->databases,
                'allocation_limit' => $plan->allocations,
                'backup_limit' => $plan->backups,
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

        \DB::transaction(function () use ($user, $plan, $server) {
            $user->decrement('wallet_balance', $plan->price);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'charge',
                'amount' => $plan->price,
                'status' => 'success',
                'description' => 'Purchased plan: ' . $plan->name . ' (server #' . $server->id . ')',
            ]);
        });

        return response()->json([
            'server_id' => $server->id,
            'message' => 'Server created successfully.',
        ]);
    }
}
