<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\View\View;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\ResourcePrice;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    public function __construct(
        protected ServerRepositoryInterface $repository,
        protected ViewFactory $view,
    ) {
    }

    /**
     * Returns the public landing page for guests, or the panel dashboard
     * (React SPA) for authenticated users.
     */
    public function index(): View
    {
        if (!auth()->check()) {
            return view('landing.index', [
                'plans' => Plan::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
                'resourcePrices' => ResourcePrice::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
            ]);
        }

        return view('templates/base.core');
    }
}
