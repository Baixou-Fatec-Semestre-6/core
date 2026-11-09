<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Subscriptions;

use App\Models\ProductMonitor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Inscrições (Alertas)')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(as: 'user')]
    public ?int $user = null;

    public function toggleStatus(int $monitorId, AuditLogger $auditLogger): void
    {
        Gate::forUser(Auth::user())->authorize('access-admin');

        $monitor = ProductMonitor::query()->with('alerts')->findOrFail($monitorId);
        $alert = $monitor->alerts()->latest('created_at')->first();

        if ($alert === null) {
            session()->flash('status', 'Este monitor não possui alerta cadastrado.');

            return;
        }

        $alert->triggered_at = $alert->triggered_at === null ? now() : null;
        $alert->save();

        $auditLogger->log(
            $alert->triggered_at === null ? 'subscription.enabled' : 'subscription.disabled',
            $alert->triggered_at === null
                ? "Inscrição #SUB-{$monitor->id} do produto #{$monitor->product_id} ativada."
                : "Inscrição #SUB-{$monitor->id} do produto #{$monitor->product_id} desativada.",
            $monitor,
        );

        session()->flash(
            'status',
            $alert->triggered_at === null
                ? "Inscrição #{$monitor->id} reativada."
                : "Inscrição #{$monitor->id} desativada.",
        );
    }

    public function render(): View
    {
        $subscriptions = ProductMonitor::query()
            ->with([
                'user',
                'product' => fn ($query) => $query->with([
                    'listings' => fn ($query) => $query
                        ->latest('last_scraped_at')
                        ->latest('id')
                        ->limit(5),
                ]),
                'alerts' => fn ($query) => $query->latest('created_at')->limit(1),
            ])
            ->whereHas('alerts')
            ->when($this->user !== null, fn ($query) => $query->where('user_id', $this->user))
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'selectedUser' => $this->user !== null ? User::query()->find($this->user) : null,
            'totalActive' => ProductMonitor::query()
                ->when($this->user !== null, fn ($query) => $query->where('user_id', $this->user))
                ->whereHas('alerts', fn ($query) => $query->whereNull('triggered_at'))
                ->count(),
        ]);
    }
}
