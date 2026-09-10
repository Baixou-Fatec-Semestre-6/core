<?php

namespace App\Services\Api;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
