<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use App\Enums\ProductListingStatus;
use App\Models\PriceHistory;
use App\Models\ProductListing;
use App\Services\ScrapeDispatcher;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;


#[Layout('components.layouts.admin')]
#[Title('Monitoramento & Scraping')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    public function retryFailedScrape(int $listingId, ScrapeDispatcher $scrapeDispatcher): void
    {
        Gate::authorize('access-admin');

        $listing = ProductListing::query()
            ->with('product')
            ->where('status', ProductListingStatus::FAILED)
            ->whereHas('product', fn (Builder $query) => $query->where('track_price', true))
            ->findOrFail($listingId);

        $scrapeDispatcher->retry($listing);

        session()->flash('status', "Anúncio #{$listing->id} reenviado ao RabbitMQ.");
    }

    public function render(): View
    {
        $now = now();
        $dayAgo = $now->copy()->subDay();
        $queuedSince = $now->copy()->subMinutes(10);
        $lastScrape = ProductListing::query()
            ->whereNotNull('last_scraped_at')
            ->max('last_scraped_at');

        $failedListings = ProductListing::query()
            ->with(['product', 'storeRecord'])
            ->where('status', ProductListingStatus::FAILED)
            ->whereHas('product')
            ->latest('last_scraped_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.monitoring.index', [
            'failedListings' => $failedListings,
            'metrics' => [
                'queuedListings' => ProductListing::query()
                    ->where('scrape_requested_at', '>=', $queuedSince)
                    ->where(fn (Builder $query) => $query
                        ->whereNull('last_scraped_at')
                        ->orWhereColumn('scrape_requested_at', '>', 'last_scraped_at'))
                    ->whereHas('product', fn (Builder $query) => $query->where('track_price', true))
                    ->count(),
                'latestScrapeAt' => $lastScrape === null ? null : Carbon::parse($lastScrape),
                'successfulLastDay' => PriceHistory::query()
                    ->where('scraped_at', '>=', $dayAgo)
                    ->count(),
                'failedListings' => ProductListing::query()
                    ->where('status', ProductListingStatus::FAILED)
                    ->whereHas('product')
                    ->count(),
                'nextScrapeAt' => when(Cache::get('scrape-dispatch:next-run'),
                    fn ($timestamp) => Carbon::createFromTimestamp($timestamp)),
                'maxAttempts' => ScrapeDispatcher::MAX_ATTEMPTS,
            ],
            'rabbitMqExchange' => config('rabbitmq.exchange'),
        ]);
    }
}
