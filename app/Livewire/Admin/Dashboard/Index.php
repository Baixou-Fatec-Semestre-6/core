<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboard;

use App\Enums\ProductListingStatus;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductMonitor;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Dashboard Operacional')]
#[Lazy]
final class Index extends Component
{
    public function placeholder(): View
    {
        return view('livewire.admin.dashboard.placeholder');
    }

    public function render(): View
    {
        $last24Hours = now()->subDay();

        return view('livewire.admin.dashboard.index', [
            'metrics' => [
                'products' => Product::query()->count(),
                'monitoredProducts' => ProductMonitor::query()
                    ->whereHas('product')
                    ->distinct()
                    ->count('product_id'),
                'users' => User::query()->where('role', UserRole::USER->value)->count(),
                'activeAlerts' => Alert::query()
                    ->whereNull('triggered_at')
                    ->whereHas('productMonitor.product')
                    ->count(),
                'priceUpdates' => PriceHistory::query()
                    ->whereHas('listing.product')
                    ->where('scraped_at', '>=', $last24Hours)
                    ->count(),
                'failedListings' => ProductListing::query()
                    ->whereHas('product')
                    ->where('status', ProductListingStatus::FAILED->value)
                    ->count(),
            ],
            'recentPriceUpdates' => PriceHistory::query()
                ->with(['listing.product'])
                ->whereHas('listing.product')
                ->latest('scraped_at')
                ->limit(5)
                ->get(),
            'recentFailedListings' => ProductListing::query()
                ->with('product')
                ->whereHas('product')
                ->where('status', ProductListingStatus::FAILED->value)
                ->latest('updated_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
