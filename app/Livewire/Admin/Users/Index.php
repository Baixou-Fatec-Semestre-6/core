<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\SendUserActivation;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\Admin\UserStatusChanged;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Usuários do Sistema')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $actor = Auth::user();
        // Livewire actions are public endpoints: authorize again, don't rely on the route alone.
        Gate::forUser($actor)->authorize('access-admin');

        $user = User::query()->findOrFail($userId);

        $oldStatus = $user->status;
        $user->status = match ($oldStatus) {
            UserStatus::DISABLED => $user->email_verified_at
                ? UserStatus::ACTIVE
                : UserStatus::PENDING,

            UserStatus::ACTIVE, UserStatus::PENDING => UserStatus::DISABLED,
        };
        $user->save();

        UserStatusChanged::dispatch($user, $actor, $oldStatus, $user->status);
    }

    public function resendActivation(int $userId, SendUserActivation $sendUserActivation): void
    {
        $actor = Auth::user();
        Gate::forUser($actor)->authorize('access-admin');

        $user = User::query()->findOrFail($userId);
        $sendUserActivation->handle($user);

        $this->resetValidation('activation');
        session()->flash('status', "E-mail de ativação reenviado para {$user->email}.");
    }

    public function render(): View
    {
        $term = trim($this->search);

        $users = User::query()
            ->where('role', UserRole::USER)
            ->withCount('productMonitors')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'hasSubscriptionsPage' => Route::has('admin.subscriptions.index'),
        ]);
    }
}
