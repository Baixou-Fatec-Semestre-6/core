<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        string $message,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        $admin = auth()->user();

        return AuditLog::create([
            'created_at' => now(),
            'admin_id' => $admin?->getKey(),
            'admin_name' => $admin?->name ?? 'System',
            'action' => $action,
            'message' => $message,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
