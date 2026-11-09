<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use App\Enums\ProductListingStatus;
use App\Enums\ScrapeRequestStatus;
use App\Models\PriceHistory;
use App\Models\ProductListing;
use App\Models\ScrapeRequest;
use App\Services\AuditLogger;
use App\Services\ScrapeDispatcher;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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

    public function retryFailedScrape(
        int $scrapeRequestId,
        ScrapeDispatcher $scrapeDispatcher,
        AuditLogger $auditLogger,
    ): void {
        Gate::authorize('access-admin');

        $scrapeRequest = ScrapeRequest::query()
            ->with('listing.product')
            ->where('status', ScrapeRequestStatus::FAILED)
            ->whereHas('listing', fn (Builder $query) => $query
                ->where('status', ProductListingStatus::FAILED)
                ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('track_price', true)))
            ->findOrFail($scrapeRequestId);
        $listing = $scrapeRequest->listing ?? abort(404);

        if (! $scrapeDispatcher->retry($listing)) {
            session()->flash('status', "Anúncio #{$listing->id} já tem uma solicitação de scraping pendente.");

            return;
        }

        $auditLogger->log(
            'scrape.retry_requested',
            "Nova tentativa de coleta solicitada para o anúncio #{$listing->id}.",
            $listing,
        );

        session()->flash('status', "Anúncio #{$listing->id} reenviado ao RabbitMQ.");
    }

    public function render(): View
    {
        $now = now();
        $dayAgo = $now->copy()->subDay();
        $lastScrape = ProductListing::query()
            ->whereNotNull('last_scraped_at')
            ->max('last_scraped_at');

        $scrapeRequests = ScrapeRequest::query()
            ->whereIn('status', [
                ScrapeRequestStatus::QUEUED,
                ScrapeRequestStatus::SUCCEEDED,
                ScrapeRequestStatus::FAILED,
                ScrapeRequestStatus::IGNORED,
            ])
            ->with('listing.product')
            ->latest('requested_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.monitoring.index', [
            'scrapeRequests' => $scrapeRequests,
            'metrics' => [
                'pendingRequests' => ScrapeRequest::query()
                    ->where('status', ScrapeRequestStatus::QUEUED)
                    ->count(),
                'latestScrapeAt' => $lastScrape === null ? null : Carbon::parse($lastScrape),
                'successfulLastDay' => PriceHistory::query()
                    ->where('scraped_at', '>=', $dayAgo)
                    ->count(),
                'failedRequests' => ScrapeRequest::query()
                    ->where('status', ScrapeRequestStatus::FAILED)
                    ->count(),
                'nextScrapeAt' => when(Cache::get('scrape-dispatch:next-run'),
                    fn ($timestamp) => Carbon::createFromTimestamp($timestamp, config('app.timezone'))),
            ],
            'rabbitMqExchange' => config('rabbitmq.exchange'),
        ]);
    }
}
