<?php

namespace App\Services;

use App\Enums\ProductListingStatus;
use App\Enums\ScrapeRequestStatus;
use App\Models\ProductListing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Publishes scrape requests for product listings. */
class ScrapeDispatcher
{
    /**
     * Create a dispatcher using the given message publisher.
     *
     * @param  RabbitMqPublisher  $publisher  Publisher used to send scrape events.
     */
    public function __construct(private RabbitMqPublisher $publisher) {}

    /**
     * Publish a request for every monitored listing that is due for scraping.
     *
     * @return int The number of listings for which requests were published.
     */
    public function dispatchDue(): int
    {
        $count = 0;

        ProductListing::query()
            ->whereHas('product.monitors')
            ->whereHas('product', fn ($query) => $query->where('track_price', true))
            ->where('status', '!=', ProductListingStatus::FAILED->value)
            ->where(fn ($q) => $q->whereNull('last_scraped_at')
                ->orWhere('last_scraped_at', '<', now()->subHour()))
            ->chunkById(200, function ($listings) use (&$count): void {
                foreach ($listings as $listing) {
                    if ($this->requestIfDue($listing)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Publish a request when no active request exists for the listing.
     *
     * Also used immediately after a user starts tracking a product.
     *
     * @param  ProductListing  $listing  Listing to scrape.
     * @return bool Whether a request was published.
     */
    public function requestIfDue(ProductListing $listing): bool
    {
        return $this->publishRequest($listing);
    }

    /**
     * Retry a failed listing when it has no active request.
     *
     * @return bool Whether a request was published.
     */
    public function retry(ProductListing $listing): bool
    {
        return $this->requestIfDue($listing);
    }

    private function hasActiveRequest(ProductListing $listing): bool
    {
        return $listing->scrapeRequests()
            ->whereIn('status', [
                ScrapeRequestStatus::DISPATCHING,
                ScrapeRequestStatus::QUEUED,
            ])
            ->exists();
    }

    private function publishRequest(ProductListing $listing): bool
    {
        $requestedAt = now();
        $requestId = (string) Str::uuid();
        $scrapeRequest = DB::transaction(function () use ($listing, $requestId, $requestedAt) {
            $lockedListing = ProductListing::query()
                ->lockForUpdate()
                ->findOrFail($listing->id);

            if ($this->hasActiveRequest($lockedListing)) {
                return null;
            }

            $lockedListing->loadMissing('product');

            return $lockedListing->scrapeRequests()->create([
                'request_id' => $requestId,
                'product_name' => $lockedListing->product->name,
                'store' => $lockedListing->store,
                'listing_url' => $lockedListing->url,
                'status' => ScrapeRequestStatus::DISPATCHING,
                'requested_at' => $requestedAt,
            ]);
        });

        if ($scrapeRequest === null) {
            return false;
        }

        try {
            $this->publisher->publish('scrape.requested', [
                'request_id' => $requestId,
                'listing_id' => $listing->id,
                'url' => $listing->url,
                'store' => $listing->store,
                'requested_at' => $requestedAt->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            $scrapeRequest->newQuery()
                ->whereKey($scrapeRequest->id)
                ->where('status', ScrapeRequestStatus::DISPATCHING)
                ->update([
                    'status' => ScrapeRequestStatus::DISPATCH_FAILED,
                    'completed_at' => now(),
                    'error_reason' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);

            throw $exception;
        }

        $scrapeRequest->newQuery()
            ->whereKey($scrapeRequest->id)
            ->where('status', ScrapeRequestStatus::DISPATCHING)
            ->update([
                'status' => ScrapeRequestStatus::QUEUED,
                'updated_at' => now(),
            ]);

        ProductListing::query()
            ->whereKey($listing->id)
            ->where(fn ($query) => $query
                ->whereNull('last_scraped_at')
                ->orWhere('last_scraped_at', '<', $requestedAt))
            ->update([
                'status' => ProductListingStatus::PENDING,
                'last_error_reason' => null,
            ]);

        return true;
    }
}
