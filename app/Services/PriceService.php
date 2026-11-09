<?php

namespace App\Services;

use App\Enums\ProductListingStatus;
use App\Jobs\SendPriceAlert;
use App\Models\Alert;
use App\Models\PriceNotification;
use App\Models\ProductListing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records listing prices and evaluates product price alerts.
 */
class PriceService
{
    /**
     * Simulate a price drop for demo purposes.
     *
     * @param  int  $productId  Product identifier.
     */
    public function simulatePriceDrop(int $productId): void
    {
        $listing = ProductListing::query()
            ->where('product_id', $productId)
            ->whereNotNull('current_price')
            ->firstOrFail();

        $this->recordPrice(
            $listing,
            round($listing->current_price * 0.95, 2), // 5% drop
            Carbon::now()
        );
    }

    /**
     * Record a scraped price and update any matching alerts.
     *
     * @param  ProductListing  $listing  Listing whose price was scraped.
     * @param  float  $newPrice  Newly scraped price.
     */
    public function recordPrice(ProductListing $listing, float $newPrice, Carbon $scrapedAt): void
    {
        DB::transaction(function () use ($listing, $newPrice, $scrapedAt): void {
            $previousLowest = $this->lowestPrice($listing->product_id);

            $listing->update([
                'current_price' => $newPrice,
                'status' => ProductListingStatus::SCRAPED,
                'last_error_reason' => null,
                'last_scraped_at' => $scrapedAt,
            ]);

            $listing->priceHistories()->create([
                'price' => $newPrice,
                'currency' => $listing->currency,
                'scraped_at' => now(),
            ]);

            $newLowest = $this->lowestPrice($listing->product_id) ?? $newPrice;

            $this->evaluateAlerts($listing->product_id, $previousLowest, $newLowest);
        });
    }

    /**
     * Mark a listing as failed after an unsuccessful scrape.
     *
     * @param  ProductListing  $listing  Listing that failed to scrape.
     * @param  string  $reason  Why the scrape failed.
     * @param  Carbon  $scrapedAt  When the attempt happened.
     */
    public function recordFailure(ProductListing $listing, string $reason, Carbon $scrapedAt): void
    {
        $listing->update([
            'status' => ProductListingStatus::FAILED,
            'last_error_reason' => $reason,
            'last_scraped_at' => $scrapedAt,
        ]);
    }

    /**
     * Get the lowest current price among scraped listings for a product.
     *
     * @param  int  $productId  Product identifier.
     * @return float|null Lowest price, or null when no scraped listings exist.
     */
    private function lowestPrice(int $productId): ?float
    {
        $lowest = ProductListing::query()
            ->where('product_id', $productId)
            ->where('status', ProductListingStatus::SCRAPED)
            ->min('current_price');

        return $lowest === null ? null : (float) $lowest;
    }

    /**
     * Trigger or re-arm alerts based on the product's latest lowest price.
     *
     * @param  int  $productId  Product identifier.
     * @param  float|null  $previousLowest  Lowest price before the update.
     * @param  float  $newLowest  Lowest price after the update.
     */
    private function evaluateAlerts(int $productId, ?float $previousLowest, float $newLowest): void
    {
        $alerts = Alert::query()
            ->withWhereHas('productMonitor', fn ($q) => $q->where('product_id', $productId))
            ->get();

        $listing = null;

        foreach ($alerts as $alert) {
            $reached = $newLowest <= (float) $alert->target_price;

            if ($reached && $alert->triggered_at === null) {
                $listing ??= ProductListing::query()
                    ->with('product')
                    ->where('product_id', $productId)
                    ->where('status', ProductListingStatus::SCRAPED)
                    ->whereNotNull('current_price')
                    ->orderBy('current_price')
                    ->firstOrFail();

                $alert->update(['triggered_at' => now()]);

                PriceNotification::create([
                    'user_id' => $alert->productMonitor->user_id,
                    'product_id' => $productId,
                    'old_price' => $previousLowest,
                    'new_price' => $newLowest,
                ]);

                SendPriceAlert::dispatch(
                    $alert->id,
                    $listing->product->name,
                    $listing->store,
                    $listing->url,
                    $alert->target_price,
                    $listing->current_price,
                    $listing->currency,
                )->afterCommit();
            } elseif (! $reached && $alert->triggered_at !== null) {
                $alert->update(['triggered_at' => null]); // re-arm
            }
        }
    }
}
