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
