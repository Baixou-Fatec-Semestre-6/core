<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

final class AuditLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
