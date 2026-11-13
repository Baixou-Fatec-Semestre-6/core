@use('App\Enums\UserRole')
@use('App\Enums\UserStatus')

<div class="space-y-4">
    @if (session()->has('status'))
        <p role="status"
            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
            {{ session('status') }}
        </p>
    @endif

    <section aria-label="Estatísticas de administradores" class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <x-admin.card class="p-4">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400">Total
                Administradores</span>
            <div class="mt-1 font-mono text-2xl font-black text-neutral-100">
                {{ number_format($stats['total'], 0, ',', '.') }}</div>
            <p class="mt-1 text-[10px] text-neutral-400">Acesso ao guard admin</p>
        </x-admin.card>

        <x-admin.card class="p-4">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400">Contas Ativas</span>
            <div class="mt-1 font-mono text-2xl font-black text-emerald-400">
                {{ number_format($stats['active'], 0, ',', '.') }}</div>
            <p class="mt-1 text-[10px] text-emerald-400">Habilitadas para login</p>
        </x-admin.card>

        <x-admin.card class="p-4">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-neutral-400">Contas Inativas</span>
            <div class="mt-1 font-mono text-2xl font-black text-neutral-400">
                {{ number_format($stats['inactive'], 0, ',', '.') }}</div>
            <p class="mt-1 text-[10px] text-neutral-400">Acesso revogado</p>
        </x-admin.card>

        <x-admin.card class="border border-purple-500/30 bg-purple-500/10 p-4">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-purple-300">Níveis de
                Permissão</span>
            <div class="mt-1 font-mono text-xs font-bold text-purple-200">
                Super Admin {{ $stats['superAdmins'] }} · Admin {{ max($stats['total'] - $stats['superAdmins'], 0) }}
            </div>
            <p class="mt-1 text-[10px] text-purple-300">RBAC baseado em papéis</p>
        </x-admin.card>
    </section>

    <x-admin.card padded class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-1 flex-col gap-3 md:flex-row md:items-center">
            <x-admin.input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Buscar admin por nome ou e-mail..." aria-label="Buscar admin por nome ou e-mail"
                autocomplete="off" class="w-full md:max-w-sm" />

            <select wire:model.live="role"
                class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs text-neutral-200 md:w-40">
                <option value="">Todas as funções</option>
                <option value="{{ UserRole::SUPER_ADMIN->value }}">Super Admin</option>
                <option value="{{ UserRole::ADMIN->value }}">Admin</option>
            </select>

            <select wire:model.live="status"
                class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs text-neutral-200 md:w-40">
                <option value="">Todos os status</option>
                <option value="{{ UserStatus::ACTIVE->value }}">Ativo</option>
                <option value="{{ UserStatus::DISABLED->value }}">Inativo</option>
                <option value="{{ UserStatus::PENDING->value }}">Pendente</option>
            </select>
        </div>

        <x-admin.button wire:click="openCreateAdminModal" wire:loading.attr="disabled"
            wire:target="openCreateAdminModal" icon="plus" class="shrink-0">Criar Administrador</x-admin.button>
    </x-admin.card>

    <x-admin.table wire:loading.delay.class="opacity-60" wire:target="search,role,status">
        <x-slot:head>
            <x-admin.th>ID</x-admin.th>
            <x-admin.th>Administrador</x-admin.th>
            <x-admin.th>E-mail</x-admin.th>
            <x-admin.th compact>Função</x-admin.th>
            <x-admin.th compact>Status</x-admin.th>
            <x-admin.th compact>Criado em</x-admin.th>
            <x-admin.th right>Ações</x-admin.th>
        </x-slot:head>

        @forelse ($admins as $admin)
                    @php
            $role = $admin->role === UserRole::SUPER_ADMIN ? ['label' => 'Super Admin', 'variant' => 'restricted'] : ['label' => 'Admin', 'variant' => 'muted'];
            $status = match ($admin->status) {
                UserStatus::ACTIVE => ['label' => 'ATIVO', 'variant' => 'success'],
                UserStatus::DISABLED => ['label' => 'INATIVO', 'variant' => 'muted'],
                UserStatus::PENDING => ['label' => 'PENDENTE', 'variant' => 'pending'],
            };
            $isCurrentUser = $admin->id === $currentUserId;
                    @endphp
                    <tr wire:key="admin-{{ $admin->id }}"
                        class="hover:bg-neutral-800/50 {{ $isCurrentUser ? 'bg-yellow-400/5' : '' }}">
                        <x-admin.td class="font-mono font-bold">#{{ $admin->id }}</x-admin.td>
                        <x-admin.td>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-[10px] font-bold text-white">
                                    {{ strtoupper(substr($admin->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-neutral-100">{{ $admin->name }}</div>
                                    @if ($isCurrentUser)
                                        <span class="text-[10px] font-mono font-semibold text-yellow-400">Você (sessão atual)</span>
                                    @endif
                                </div>
                            </div>
                        </x-admin.td>
                        <x-admin.td compact class="font-mono">{{ $admin->email }}</x-admin.td>
                        <x-admin.td compact>
                            <x-admin.badge :variant="$role['variant']">
                                {{ $role['label'] }}
                            </x-admin.badge>
                        </x-admin.td>
                        <x-admin.td compact>
                            <x-admin.badge :variant="$status['variant']">
                                {{ $status['label'] }}
                            </x-admin.badge>
                        </x-admin.td>
                        <x-admin.td compact
                            class="font-mono text-neutral-400">{{ $admin->created_at?->format('d/m/Y') }}</x-admin.td>
                        <x-admin.td right>
                            <div class="grid grid-cols-[8rem_3rem_5rem] items-center justify-end gap-2">
                                <div>
                                    <x-admin.button variant="secondary" wire:click="sendResetLink({{ $admin->id }})"
                                        wire:loading.attr="disabled" wire:target="sendResetLink({{ $admin->id }})"
                                        class="w-full whitespace-nowrap">Redefinir senha</x-admin.button>
                                </div>
                                <div class="text-right">
                                    <x-admin.button variant="link-accent" wire:click="editAdmin({{ $admin->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="editAdmin({{ $admin->id }})">Editar</x-admin.button>
                                </div>
                                <div class="text-right">
                                    @if (! $isCurrentUser)
                                        <x-admin.button
                                            :variant="$admin->status === UserStatus::DISABLED ? 'link-success' : 'link-danger'"
                                            wire:click="toggleStatus({{ $admin->id }})" wire:loading.attr="disabled"
                                            wire:target="toggleStatus({{ $admin->id }})"
                                            wire:confirm="{{ $admin->status === UserStatus::DISABLED ? 'Ativar' : 'Desativar' }} a conta de {{ $admin->name }}?">
                                            {{ $admin->status === UserStatus::DISABLED ? 'Ativar' : 'Desativar' }}
                                        </x-admin.button>
                                    @else
                                        <span class="text-[10px] italic text-neutral-500">Conta protegida</span>
                                    @endif
                                </div>
                            </div>
                        </x-admin.td>
                    </tr>
        @empty
            <tr>
                <td colspan="7" class="py-10 px-4 text-center text-neutral-400">Nenhum administrador encontrado.</td>
            </tr>
        @endforelse

        @if ($admins->hasPages())
            <x-slot:footer>
                {{ $admins->links('pagination.admin') }}
            </x-slot:footer>
        @endif
    </x-admin.table>

    <x-admin.modal name="admin-form" :title="$editingAdminId === null ? 'Cadastrar Administrador' : 'Editar Administrador'" icon="users">
        <form wire:submit="saveAdmin" class="space-y-3.5 p-5 text-xs">
            <div>
                <label for="admin-name" class="mb-1 block font-bold text-neutral-300">Nome Completo</label>
                <x-admin.input id="admin-name" wire:model="adminName" type="text" required autocomplete="name" />
                @error('adminName')
                <p class="mt-1 text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="admin-email" class="mb-1 block font-bold text-neutral-300">E-mail Institucional</label>
                <x-admin.input id="admin-email" wire:model="adminEmail" type="email" required autocomplete="email" />
                @error('adminEmail')
                <p class="mt-1 text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="admin-role" class="mb-1 block font-bold text-neutral-300">Função Administrativa</label>
                <select id="admin-role" wire:model="adminRole" @disabled($editingAdminId === $currentUserId)
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2 text-neutral-200">
                    <option value="{{ UserRole::ADMIN->value }}">Admin</option>
                    <option value="{{ UserRole::SUPER_ADMIN->value }}">Super Admin</option>
                </select>
            </div>

            <div>
                <label for="admin-status" class="mb-1 block font-bold text-neutral-300">Status da Conta</label>
                <select id="admin-status" wire:model="adminStatus" @disabled($editingAdminId === $currentUserId)
                    class="w-full rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2 text-neutral-200">
                    <option value="{{ UserStatus::ACTIVE->value }}">Ativo</option>
                    <option value="{{ UserStatus::DISABLED->value }}">Inativo</option>
                    <option value="{{ UserStatus::PENDING->value }}">Pendente</option>
                </select>
            </div>

            @if ($editingAdminId === null)
                <label
                    class="flex cursor-pointer items-center gap-2 rounded border border-neutral-700 bg-neutral-800/50 p-3">
                    <input type="checkbox" wire:model="sendResetLinkOnCreate"
                        class="rounded border-neutral-700 text-yellow-400">
                    <span class="font-semibold text-neutral-200">Enviar e-mail para criação de senha</span>
                </label>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-admin.button type="button" variant="secondary"
                    x-on:click="$dispatch('close-modal', 'admin-form')">Cancelar</x-admin.button>
                <x-admin.button type="submit" wire:loading.attr="disabled" wire:target="saveAdmin">
                    {{ $editingAdminId === null ? 'Criar Administrador' : 'Salvar Alterações' }}
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>