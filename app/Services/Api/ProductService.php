<?php

namespace App\Services\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get all products with optional search filter and pagination.
     */
    public function getAll(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with('listings');

        if (! empty($search)) {
            $query->whereLike('name', '%'.addcslashes($search, '%_').'%');
        }

        return $query->paginate($perPage);
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
     * Start monitoring every active listing for a product.
     */
    public function track(int $productId, User $user): Product
    {
        return DB::transaction(function () use ($productId, $user): Product {
            $product = Product::findOrFail($productId);

            foreach ($product->listings as $listing) {
                $listing->monitors()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'target_price' => $listing->current_price,
                        'currency' => $listing->currency,
                    ],
                );
            }

            return $product->load([
                'listings.monitors' => fn ($query) => $query->where('user_id', $user->id),
            ]);
        });
    }
}
