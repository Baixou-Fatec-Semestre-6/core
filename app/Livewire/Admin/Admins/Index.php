<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Admins;

use App\Actions\SendUserActivation;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\Admin\UserStatusChanged;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Administração do Sistema')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    // ---------------------------------------------------------------------
    // Listing filters (synced with the URL)
    // ---------------------------------------------------------------------

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $role = '';

    #[Url(as: 'status')]
    public string $status = '';

    // ---------------------------------------------------------------------
    // Create / edit modal form
    // ---------------------------------------------------------------------

    public ?int $editingAdminId = null;

    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminRole = 'admin';

    public string $adminStatus = 'pending';

    public bool $sendResetLink = true;

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------

    /** Any filter change sends the user back to the first page. */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    // ---------------------------------------------------------------------
    // Modal actions
    // ---------------------------------------------------------------------

    public function openCreateAdminModal(): void
    {
        Gate::authorize('manage-admins');

        $this->resetAdminForm();
        $this->dispatch('open-modal', name: 'admin-form');
    }

    public function editAdmin(int $userId): void
    {
        Gate::authorize('manage-admins');

        $admin = $this->findAdmin($userId);

        $this->editingAdminId = $admin->id;
        $this->adminName = $admin->name;
        $this->adminEmail = $admin->email;
        $this->adminRole = $admin->role->value;
        $this->adminStatus = $admin->status->value;
        $this->sendResetLink = false;

        $this->resetValidation();
        $this->dispatch('open-modal', name: 'admin-form');
    }

    public function saveAdmin(SendUserActivation $sendUserActivation, AuditLogger $auditLogger): void
    {
        Gate::authorize('manage-admins');

        $admin = $this->editingAdminId === null
            ? new User
            : $this->findAdmin($this->editingAdminId);

        $isNew = ! $admin->exists;
        $oldStatus = $isNew ? null : $admin->status;

        $data = $this->validate($this->adminRules($admin));

        $emailChanged = ! $isNew && $admin->email !== $data['adminEmail'];

        $this->fillAdmin($admin, $data, $isNew, $emailChanged);
        $admin->save();

        $auditLogger->log(
            $isNew ? 'admin.created' : 'admin.updated',
            $isNew
                ? "Administrador #{$admin->id} criado."
                : "Administrador #{$admin->id} atualizado.",
            $admin,
        );

        // Notifications / side effects
        if ($oldStatus !== null && $oldStatus !== $admin->status) {
            UserStatusChanged::dispatch($admin, Auth::user(), $oldStatus, $admin->status);
        }

        // A brand-new admin has no old status, so "$oldStatus !== PENDING" covers the new case too.
        $needsActivation = $admin->status === UserStatus::PENDING
            && $admin->email_verified_at === null
            && ($emailChanged || $oldStatus !== UserStatus::PENDING);

        if ($needsActivation) {
            $sendUserActivation->handle($admin);
        }

        if ($isNew && $this->sendResetLink) {
            Password::sendResetLink(['email' => $admin->email]);
        }

        $this->flash($isNew
            ? "Administrador #{$admin->id} criado com sucesso."
            : "Administrador #{$admin->id} atualizado com sucesso.");

        $this->resetAdminForm();
        $this->dispatch('close-modal', name: 'admin-form');
    }

    // ---------------------------------------------------------------------
    // Row actions
    // ---------------------------------------------------------------------

    public function toggleStatus(int $userId, AuditLogger $auditLogger): void
    {
        Gate::authorize('manage-admins');

        $actor = Auth::user();
        $user = User::query()->findOrFail($userId);

        if ($actor?->id === $user->id) {
            $this->flash('Você não pode desativar sua própria conta de super administrador.');

            return;
        }

        $oldStatus = $user->status;
        $user->status = $this->statusAfterToggle($user);
        $user->save();

        $statusLabel = match ($user->status) {
            UserStatus::DISABLED => 'desativado',
            UserStatus::PENDING => 'pendente de confirmação',
            UserStatus::ACTIVE => 'ativo',
        };

        $auditLogger->log(
            'admin.status_changed',
            "Status do administrador #{$user->id} alterado para {$statusLabel}.",
            $user,
        );

        UserStatusChanged::dispatch($user, $actor, $oldStatus, $user->status);

        $this->flash("Conta #{$user->id} marcada como {$user->status->value}.");
    }

    public function sendResetLink(int $userId, AuditLogger $auditLogger): void
    {
        Gate::authorize('manage-admins');

        $user = User::query()->findOrFail($userId);
        Password::sendResetLink(['email' => $user->email]);

        $auditLogger->log(
            'admin.password_reset_requested',
            "Link de redefinição de senha solicitado para o administrador #{$user->id}.",
            $user,
        );

        $this->flash("E-mail de redefinição enviado para {$user->email}.");
    }

    public function deleteAdmin(int $userId, AuditLogger $auditLogger): void
    {
        Gate::authorize('manage-admins');

        $admin = $this->findAdmin($userId);

        if ($admin->id === Auth::id()) {
            $this->flash('Você não pode excluir sua própria conta de administrador.');

            return;
        }

        if ($this->isLastActiveSuperAdmin($admin)) {
            $this->flash('Não é possível excluir o último super administrador ativo.');

            return;
        }

        $admin->delete();

        $auditLogger->log(
            'admin.deleted',
            "Administrador #{$admin->id} excluído.",
            $admin,
        );

        $this->flash("Administrador #{$admin->id} excluído com sucesso.");
    }

    // ---------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------

    public function render(): View
    {
        Gate::authorize('manage-admins');

        return view('livewire.admin.admins.index', [
            'admins' => $this->filteredAdmins()->latest()->paginate(self::PER_PAGE),
            'stats' => $this->stats(),
            'currentUserId' => Auth::id(),
        ]);
    }

    // ---------------------------------------------------------------------
    // Queries
    // ---------------------------------------------------------------------

    /** Every user that is an admin or super admin. */
    private function adminsQuery(): Builder
    {
        return User::query()->whereIn('role', [UserRole::ADMIN, UserRole::SUPER_ADMIN]);
    }

    private function findAdmin(int $userId): User
    {
        return $this->adminsQuery()->findOrFail($userId);
    }

    /** Admins narrowed down by the search / role / status filters. */
    private function filteredAdmins(): Builder
    {
        $term = trim($this->search);

        return $this->adminsQuery()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($this->role !== '', fn (Builder $query) => $query->where('role', $this->role))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status));
    }

    /** @return array{total:int, active:int, inactive:int, superAdmins:int} */
    private function stats(): array
    {
        return [
            'total' => $this->adminsQuery()->count(),
            'active' => $this->adminsQuery()->where('status', UserStatus::ACTIVE)->count(),
            'inactive' => $this->adminsQuery()->where('status', UserStatus::DISABLED)->count(),
            'superAdmins' => $this->adminsQuery()->where('role', UserRole::SUPER_ADMIN)->count(),
        ];
    }

    // ---------------------------------------------------------------------
    // Business rules
    // ---------------------------------------------------------------------

    /** Disabled accounts come back as active (if verified) or pending; anything else becomes disabled. */
    private function statusAfterToggle(User $user): UserStatus
    {
        if ($user->status !== UserStatus::DISABLED) {
            return UserStatus::DISABLED;
        }

        return $user->email_verified_at ? UserStatus::ACTIVE : UserStatus::PENDING;
    }

    private function isLastActiveSuperAdmin(User $admin): bool
    {
        if ($admin->role !== UserRole::SUPER_ADMIN || $admin->status !== UserStatus::ACTIVE) {
            return false;
        }

        $activeSuperAdmins = User::query()
            ->where('role', UserRole::SUPER_ADMIN)
            ->where('status', UserStatus::ACTIVE)
            ->count();

        return $activeSuperAdmins <= 1;
    }

    // ---------------------------------------------------------------------
    // Form helpers
    // ---------------------------------------------------------------------

    /** @return array<string, array<int, mixed>> */
    private function adminRules(User $admin): array
    {
        return [
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'adminRole' => ['required', Rule::in([UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value])],
            'adminStatus' => ['required', Rule::in(array_column(UserStatus::cases(), 'value'))],
        ];
    }

    /**
     * Copies validated form data onto the model (without saving).
     *
     * - Changing the email invalidates its verification.
     * - You can't change your own role or status.
     * - An unverified account can never be "active"; it stays pending.
     * - New admins get a random password (they set their own via the reset link).
     *
     * @param  array<string, mixed>  $data
     */
    private function fillAdmin(User $admin, array $data, bool $isNew, bool $emailChanged): void
    {
        $isCurrentUser = $admin->exists && $admin->id === Auth::id();

        $admin->name = $data['adminName'];
        $admin->email = $data['adminEmail'];

        if ($emailChanged) {
            $admin->email_verified_at = null;
        }

        if (! $isCurrentUser) {
            $admin->role = UserRole::from($data['adminRole']);
        }

        $requestedStatus = $isCurrentUser
            ? $admin->status
            : UserStatus::from($data['adminStatus']);

        $admin->status = $requestedStatus === UserStatus::ACTIVE && $admin->email_verified_at === null
            ? UserStatus::PENDING
            : $requestedStatus;

        if ($isNew) {
            $admin->password = Str::random(40);
        }
    }

    private function resetAdminForm(): void
    {
        $this->reset([
            'adminName',
            'adminEmail',
            'adminRole',
            'adminStatus',
            'sendResetLink',
            'editingAdminId',
        ]);
        $this->resetValidation();
    }

    private function flash(string $message): void
    {
        session()->flash('status', $message);
    }
}
