<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'image_url',
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(ProductListing::class);
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(ProductMonitor::class);
    }
}