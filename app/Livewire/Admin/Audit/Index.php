<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Log de Auditoria')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    public function boot(): void
    {
        Gate::authorize('manage-audit-logs');
    }

    public function render(): View
    {
        return view('livewire.admin.audit.index', [
            'auditLogs' => AuditLog::query()
                ->select([
                    '_id',
                    'created_at',
                    'admin_id',
                    'admin_name',
                    'action',
                    'message',
                    'subject_type',
                    'subject_id',
                    'ip',
                    'user_agent',
                    'metadata',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('_id')
                ->paginate(self::PER_PAGE),
        ]);
    }
}
