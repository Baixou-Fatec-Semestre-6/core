<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Log de Auditoria')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public function boot(): void
    {
        Gate::authorize('manage-audit-logs');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'fromDate', 'toDate']);
        $this->resetValidation(['fromDate', 'toDate']);
        $this->resetPage();
    }

    public function render(): View
    {
        $rules = [
            'fromDate' => ['nullable', 'date_format:Y-m-d'],
            'toDate' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($this->fromDate !== '') {
            $rules['toDate'][] = 'after_or_equal:fromDate';
        }

        $this->validate($rules);

        $term = trim($this->search);

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
                ->when($term !== '', function (Builder $query) use ($term): void {
                    $like = '%'.addcslashes($term, '%_\\').'%';

                    $query->where(fn (Builder $query) => $query
                        ->where('admin_name', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhere('ip', 'like', $like)
                        ->orWhere('subject_type', 'like', $like));
                })
                ->when($this->fromDate !== '', fn (Builder $query) => $query
                    ->where('created_at', '>=', Carbon::parse($this->fromDate)->startOfDay()))
                ->when($this->toDate !== '', fn (Builder $query) => $query
                    ->where('created_at', '<=', Carbon::parse($this->toDate)->endOfDay()))
                ->orderByDesc('created_at')
                ->orderByDesc('_id')
                ->paginate(self::PER_PAGE),
        ]);
    }
}
