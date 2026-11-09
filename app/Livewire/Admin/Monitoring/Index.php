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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Monitoramento & Scraping')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'fromDate', 'toDate']);
        $this->resetValidation(['status', 'fromDate', 'toDate']);
        $this->resetPage();
    }

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
        $validationRules = [
            'status' => ['nullable', Rule::in(array_map(
                static fn (ScrapeRequestStatus $status): string => $status->value,
                [
                    ScrapeRequestStatus::QUEUED,
                    ScrapeRequestStatus::SUCCEEDED,
                    ScrapeRequestStatus::FAILED,
                    ScrapeRequestStatus::IGNORED,
                ],
            ))],
            'fromDate' => ['nullable', 'date_format:Y-m-d'],
            'toDate' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($this->fromDate !== '') {
            $validationRules['toDate'][] = 'after_or_equal:fromDate';
        }

        $this->validate($validationRules);

        $now = now();
        $dayAgo = $now->copy()->subDay();
        $term = trim($this->search);
        $visibleStatuses = [
            ScrapeRequestStatus::QUEUED,
            ScrapeRequestStatus::SUCCEEDED,
            ScrapeRequestStatus::FAILED,
            ScrapeRequestStatus::IGNORED,
        ];
        $lastScrape = ProductListing::query()
            ->whereNotNull('last_scraped_at')
            ->max('last_scraped_at');

        $scrapeRequests = ScrapeRequest::query()
            ->whereIn('status', $visibleStatuses)
            ->when($this->status !== '', fn (Builder $query) => $query
                ->where('status', $this->status))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('request_id', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('store', 'like', $like)
                    ->orWhere('listing_url', 'like', $like)
                    ->orWhere('error_reason', 'like', $like));
            })
            ->when($this->fromDate !== '', fn (Builder $query) => $query
                ->where('requested_at', '>=', Carbon::parse($this->fromDate)->startOfDay()))
            ->when($this->toDate !== '', fn (Builder $query) => $query
                ->where('requested_at', '<=', Carbon::parse($this->toDate)->endOfDay()))
            ->with('listing.product')
            ->latest('requested_at')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.monitoring.index', [
            'scrapeRequests' => $scrapeRequests,
            'totalScrapeRequests' => ScrapeRequest::query()->whereIn('status', $visibleStatuses)->count(),
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
