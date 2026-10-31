<?php

namespace App\Services\Api;

use App\Models\Product;
use App\Models\User;
use App\Services\ScrapeDispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly ScrapeDispatcher $scrapeDispatcher) {}

    /**
     * Get all products with optional search filter and pagination.
     */
    public function getAll(
        ?string $search,
        int $perPage,
        ?string $scope,
        User $user,
    ): LengthAwarePaginator {
        return Product::with('listings')
            ->when($scope === 'tracked', fn ($query) => $query->withWhereHas(
                'monitors',
                fn ($monitorQuery) => $monitorQuery->where('user_id', $user->id)
            ))
            ->when($search, fn ($query) => $query->whereLike(
                'name',
                '%'.addcslashes($search, '%_').'%'
            ))
            ->paginate($perPage);
    }

    /**
     * Get a specific product by ID.
     */
    public function getById(int $productId): Product
    {
        return Product::with('listings')->findOrFail($productId);
    }

    /**
     * Get price history for a specific product.
     */
    public function getPriceHistory(int $productId, int $periodDays = 90): Product
    {
        $cutoffDate = now()->subDays($periodDays);

        return Product::with(['listings.priceHistories' => function ($query) use ($cutoffDate) {
            $query->where('scraped_at', '>=', $cutoffDate)->orderBy('scraped_at');
        }])->findOrFail($productId);
    }

    /**
     * Start monitoring a product for a user.
     */
    public function track(int $productId, User $user): Product
    {
        $product = Product::findOrFail($productId);

        $product->monitors()->firstOrCreate(['user_id' => $user->id]);

        // Request a scrape for each listing of the product
        foreach ($product->listings as $listing) {
            $this->scrapeDispatcher->requestIfDue($listing);
        }

        return $product->load([
            'listings',
            'monitors' => fn ($query) => $query->where('user_id', $user->id),
        ]);
    }

    /**
     * Stop monitoring a product for a user.
     */
    public function untrack(int $productId, User $user): void
    {
        DB::transaction(function () use ($productId, $user): void {
            Product::findOrFail($productId)
                ->monitors()
                ->where('user_id', $user->id)
                ->delete();
        });
    }
}
