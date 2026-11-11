<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image_source_url',
        'image_path',
        'track_price',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->image_path)
            ? Storage::url($this->image_path)
            : $this->image_source_url);
    }

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
