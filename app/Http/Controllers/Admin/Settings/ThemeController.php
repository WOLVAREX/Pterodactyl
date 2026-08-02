<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Support\ThemeColorGenerator;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ThemeController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the theme picker / customizer UI.
     */
    public function index(): View
    {
        $default = ThemeColorGenerator::PRESETS['default'];

        return view('admin.settings.theme', [
            'presets' => ThemeColorGenerator::PRESETS,
            'currentPreset' => $this->settings->get('settings::theme:preset', 'default'),
            'neutral' => $this->settings->get('settings::theme:neutral', $default['neutral']),
            'accent' => $this->settings->get('settings::theme:accent', $default['accent']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preset' => 'nullable|string|max:64',
            'neutral' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $this->settings->set('settings::theme:preset', $data['preset'] ?? 'custom');
        $this->settings->set('settings::theme:neutral', strtolower($data['neutral']));
        $this->settings->set('settings::theme:accent', strtolower($data['accent']));

        $this->alert->success('The panel theme has been updated. Refresh any open tabs to see the changes.')->flash();

        return redirect()->route('admin.settings.theme');
    }
}
