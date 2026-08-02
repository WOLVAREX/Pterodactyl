<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function __construct(protected AlertsMessageBag $alert)
    {
    }

    protected function eggOptions()
    {
        return Egg::with('nest')->orderBy('nest_id')->orderBy('name')->get()->groupBy(function ($egg) {
            return $egg->nest->name ?? 'Uncategorized';
        });
    }

    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::orderBy('sort_order')->orderBy('id')->get(),
            'eggGroups' => $this->eggOptions(),
        ]);
    }

    public function view(Plan $plan): View
    {
        return view('admin.plans.view', [
            'plan' => $plan,
            'eggGroups' => $this->eggOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Plan::create($data);

        $this->alert->success('Plan was created successfully.')->flash();

        return redirect()->route('admin.plans');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        if ($request->input('action') === 'delete') {
            $plan->delete();
            $this->alert->success('Plan was deleted successfully.')->flash();

            return redirect()->route('admin.plans');
        }

        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        $plan->update($data);

        $this->alert->success('Plan was updated successfully.')->flash();

        return redirect()->route('admin.plans.view', $plan->id);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'billing_period' => 'required|string|max:20',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'cpu' => 'required|integer|min:0',
            'databases' => 'required|integer|min:0',
            'backups' => 'required|integer|min:0',
            'allocations' => 'required|integer|min:0',
            'features' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'egg_id' => 'nullable|integer|exists:eggs,id',
        ]);

        if (!empty($data['egg_id'])) {
            $data['nest_id'] = Egg::query()->findOrFail($data['egg_id'])->nest_id;
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
