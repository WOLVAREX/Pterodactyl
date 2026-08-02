<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Support\ThemeColorGenerator;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class AssetComposer
{
    /**
     * AssetComposer constructor.
     */
    public function __construct(
        private AssetHashService $assetHashService,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', [
            'name' => config('app.name') ?? 'Pterodactyl',
            'locale' => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
        ]);
        $view->with('themeVariables', $this->themeVariables());
    }

    /**
     * @return array<string, string>
     */
    protected function themeVariables(): array
    {
        $default = ThemeColorGenerator::PRESETS['default'];

        $neutral = $this->settings->get('settings::theme:neutral', $default['neutral']);
        $accent = $this->settings->get('settings::theme:accent', $default['accent']);

        if (!ThemeColorGenerator::isValidHex($neutral)) {
            $neutral = $default['neutral'];
        }

        if (!ThemeColorGenerator::isValidHex($accent)) {
            $accent = $default['accent'];
        }

        return ThemeColorGenerator::variables($neutral, $accent);
    }
}
