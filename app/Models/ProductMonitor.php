<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductMonitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'listing_id',
        'target_price',
        'currency',
        'notification_channel',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
            'notification_channel' => NotificationChannel::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'listing_id');
    }
}
