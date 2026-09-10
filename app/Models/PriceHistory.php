<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $fillable = [
        'listing_id',
        'price',
        'currency',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'scraped_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'listing_id');
    }
}
