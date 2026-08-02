<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;

class CurrencyController extends Controller
{
    public function __construct(protected AlertsMessageBag $alert)
    {
    }

    public function index(): View
    {
        return view('admin.currencies.index', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'symbol' => 'required|string|max:10',
            'rate_to_kes' => 'required|numeric|min:0.000001',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        Currency::create($data);

        $this->alert->success('Currency added successfully.')->flash();

        return redirect()->route('admin.currencies');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        if ($request->input('action') === 'delete') {
            if ($currency->code === 'KES') {
                $this->alert->danger('KES is the base currency and cannot be deleted.')->flash();

                return redirect()->route('admin.currencies');
            }

            $currency->delete();
            $this->alert->success('Currency deleted.')->flash();

            return redirect()->route('admin.currencies');
        }

        $data = $request->validate([
            'symbol' => 'required|string|max:10',
            'rate_to_kes' => 'required|numeric|min:0.000001',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($currency->code === 'KES') {
            $data['rate_to_kes'] = 1;
        }

        $currency->update($data);

        $this->alert->success('Currency updated.')->flash();

        return redirect()->route('admin.currencies');
    }
}
