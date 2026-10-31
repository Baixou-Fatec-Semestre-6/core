<?php

namespace App\Services;

use App\Models\ProductListing;
use Illuminate\Support\Str;

/** Publishes scrape requests for product listings. */
class ScrapeDispatcher
{
    /**
     * Create a dispatcher using the given message publisher.
     *
     * @param  RabbitMqPublisher  $publisher  Publisher used to send scrape events.
     */
    public function __construct(private RabbitMqPublisher $publisher)
    {
    }

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
     * Publish a request if the listing has not been requested within the cooldown.
     *
     * Also used immediately after a user starts tracking a product.
     *
     * @param  ProductListing  $listing  Listing to scrape.
     * @return bool Whether a request was published.
     */
    public function requestIfDue(ProductListing $listing): bool
    {
        if (
            $listing->scrape_requested_at !== null
            && $listing->scrape_requested_at->gte(now()->subMinutes(10))
        ) {
            return false;
        }

        $this->publisher->publish('scrape.requested', [
            'event_id' => (string) Str::uuid(),
            'listing_id' => $listing->id,
            'url' => $listing->url,
            'store' => $listing->store,
            'requested_at' => now()->toIso8601String(),
        ]);

        $listing->forceFill(['scrape_requested_at' => now()])->saveQuietly();

        return true;
    }
}
