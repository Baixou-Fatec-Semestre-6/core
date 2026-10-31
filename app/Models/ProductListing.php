<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'store',
        'url',
        'current_price',
        'currency',
        'last_scraped_at',
        'last_scrape_status',
        'last_scrape_error',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'last_scraped_at' => 'datetime',
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

    public function monitors(): HasMany
    {
        return $this->hasMany(ProductMonitor::class, 'listing_id');
    }
}
