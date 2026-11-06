<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image_url',
        'track_price',
    ];

    protected function casts(): array
    {
        return [
            'track_price' => 'boolean',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(ProductListing::class);
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(ProductMonitor::class);
    }

    public function priceNotifications(): HasMany
    {
        return $this->hasMany(PriceNotification::class);
    }
}
