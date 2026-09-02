<?php

namespace App\Models;

use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'code_hash',
        'type',
        'expires_at',
        'attempts',
        'used_at',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'type' => VerificationType::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < 3;
    }
}