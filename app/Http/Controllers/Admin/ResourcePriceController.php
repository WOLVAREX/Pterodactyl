<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\ResourcePrice;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;

class ResourcePriceController extends Controller
{
    public function __construct(protected AlertsMessageBag $alert)
    {
    }

    public function index(): View
    {
        return view('admin.resource-prices.index', [
            'items' => ResourcePrice::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        ResourcePrice::create($data);

        $this->alert->success('Resource price added.')->flash();

        return redirect()->route('admin.resource-prices');
    }

    public function update(Request $request, ResourcePrice $resourcePrice): RedirectResponse
    {
        if ($request->input('action') === 'delete') {
            $resourcePrice->delete();
            $this->alert->success('Resource price deleted.')->flash();

            return redirect()->route('admin.resource-prices');
        }

        $data = $this->validated($request);
        $resourcePrice->update($data);

        $this->alert->success('Resource price updated.')->flash();

        return redirect()->route('admin.resource-prices');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'resource_key' => 'nullable|in:ram,disk,cpu,database,backup,allocation',
            'unit_label' => 'required|string|max:100',
            'price_kes' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
