<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

final class Sidebar extends Component
{
    /** @var list<array{label: string, href: ?string, active: bool, icon: string, iconClass: string, badge: ?string, badgeVariant: string}> */
    public array $items;

    public function __construct()
    {
        $this->items = array_map($this->resolveItem(...), $this->definition());
    }

    public function render(): View
    {
        return view('components.admin.sidebar');
    }

    /**
     * The navigation. Order here = numbering in the UI ("1. Dashboard", ...).
     *
     * `route`/`active`: items whose route is not registered yet render as inert links,
     * so you can add the other sections one at a time.
     * `badge`: closure returning int|string|null; ints are formatted pt-BR (1.248).
     *
     * @return list<array<string, mixed>>
     */
    private function definition(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'active' => 'admin.dashboard',
                'icon' => 'home',
            ],
            [
                'label' => 'Produtos',
                'route' => 'admin.products.index',
                'active' => 'admin.products.*',
                'icon' => 'cube',
                // 'badge' => fn () => $this->cached('products', fn () => Product::count()),
            ],
            [
                'label' => 'Monitoramento & Scraping',
                'route' => 'admin.monitoring.index',
                'active' => 'admin.monitoring.*',
                'icon' => 'bolt',
                'iconClass' => 'text-emerald-400',
                'badgeVariant' => 'count-danger',
                // 'badge' => fn () => ($n = ScrapeJob::failed()->count()) > 0 ? "{$n} falhas" : null,
            ],
            [
                'label' => 'Usuários',
                'route' => 'admin.users.index',
                'active' => 'admin.users.*',
                'icon' => 'users',
                'badge' => fn () => $this->cached(
                    'users:regular',
                    fn () => User::query()->where('role', UserRole::USER->value)->count(),
                ),
            ],
            [
                'label' => 'Inscrições (Alertas)',
                'route' => 'admin.subscriptions.index',
                'active' => 'admin.subscriptions.*',
                'icon' => 'bell',
                'iconClass' => 'text-orange-400',
                // 'badge' => fn () => $this->cached('subscriptions', fn () => Subscription::query()->where('is_active', true)->count()),
            ],
            [
                'label' => 'Log de Auditoria',
                'route' => 'admin.audit.index',
                'active' => 'admin.audit.*',
                'icon' => 'document',
                'iconClass' => 'text-neutral-400',
            ],
            [
                'label' => 'Administradores',
                'route' => 'admin.admins.index',
                'active' => 'admin.admins.*',
                'icon' => 'shield',
                'iconClass' => 'text-purple-400',
                'badgeVariant' => 'count-purple',
                // 'badge' => fn () => $this->cached('admins', fn () => Admin::query()->count()),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, href: ?string, active: bool, icon: string, iconClass: string, badge: ?string, badgeVariant: string}
     */
    private function resolveItem(array $item): array
    {
        $badge = isset($item['badge']) ? value($item['badge']) : null;

        return [
            'label' => $item['label'],
            'href' => Route::has($item['route']) ? route($item['route']) : null,
            'active' => request()->routeIs($item['active']),
            'icon' => $item['icon'],
            'iconClass' => $item['iconClass'] ?? '',
            'badge' => is_int($badge) ? number_format($badge, 0, ',', '.') : $badge,
            'badgeVariant' => $item['badgeVariant'] ?? 'count',
        ];
    }

    /**
     * Sidebar counters hit on every admin page; keep them cheap.
     *
     * @param  Closure(): int  $callback
     */
    private function cached(string $key, Closure $callback): int
    {
        return Cache::remember("admin:nav:{$key}", 60, $callback);
    }
}
