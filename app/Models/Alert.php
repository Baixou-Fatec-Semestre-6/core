<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $attributes = [
        'notification_channel' => NotificationChannel::ALL->value,
    ];

    protected $fillable = [
        'product_monitor_id',
        'target_price',
        'notification_channel',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
            'notification_channel' => NotificationChannel::class,
        ];
    }

    public function productMonitor(): BelongsTo
    {
        return $this->belongsTo(ProductMonitor::class);
    }
}
