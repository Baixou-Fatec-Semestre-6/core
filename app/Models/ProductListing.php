<?php

namespace App\Models;

use App\Enums\ProductListingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductListing extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => ProductListingStatus::PENDING->value,
    ];

    protected $fillable = [
        'product_id',
        'store',
        'url',
        'current_price',
        'currency',
        'last_scraped_at',
        'status',
        'last_error_reason',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'last_scraped_at' => 'datetime',
            'scrape_requested_at' => 'datetime',
            'status' => ProductListingStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'listing_id');
    }
}
