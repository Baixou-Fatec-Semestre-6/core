<?php

namespace App\Models;

use App\Enums\ScrapeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapeRequest extends Model
{
    protected $fillable = [
        'request_id',
        'product_listing_id',
        'product_name',
        'store',
        'listing_url',
        'status',
        'requested_at',
        'completed_at',
        'error_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScrapeRequestStatus::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'product_listing_id');
    }
}
