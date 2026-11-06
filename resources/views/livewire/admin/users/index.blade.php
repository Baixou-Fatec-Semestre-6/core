@use('Illuminate\Support\Facades\Route')
@use('App\Enums\UserStatus')

<div class="space-y-4">
    {{-- Toolbar --}}
    <x-admin.card padded class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-admin.input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar usuário por nome ou e-mail..."
            aria-label="Buscar usuário por nome ou e-mail"
            autocomplete="off"
            class="w-full sm:w-72"
        />

        <div class="flex items-center justify-between gap-3">
            <span wire:loading.delay wire:target="search" class="text-xs font-mono text-neutral-400">
                Buscando…
            </span>
            <span class="text-xs font-mono text-neutral-400">
                Total: {{ number_format($users->total(), 0, ',', '.') }} {{ $this->search === '' ? 'contas cadastradas' : 'contas encontradas' }}
            </span>
        </div>
    </x-admin.card>

    @if (session()->has('status'))
        <p role="status" class="text-sm text-emerald-300">{{ session('status') }}</p>
    @endif

    @error('activation')
        <p role="alert" class="text-sm text-red-300">{{ $message }}</p>
    @enderror

    {{-- Users table --}}
    <x-admin.table wire:loading.delay.class="opacity-60" wire:target="search">
        <x-slot:head>
            <x-admin.th>ID</x-admin.th>
            <x-admin.th>Usuário</x-admin.th>
            <x-admin.th compact>E-mail</x-admin.th>
            <x-admin.th compact>Status</x-admin.th>
            <x-admin.th compact>Produtos Monitorados</x-admin.th>
            <x-admin.th compact>Criado em</x-admin.th>
            <x-admin.th right>Ações do Admin</x-admin.th>
        </x-slot:head>

        @forelse ($users as $user)
            @php
                $status = match ($user->status) {
                    UserStatus::PENDING => ['label' => 'PENDENTE', 'variant' => 'pending'],
                    UserStatus::ACTIVE => ['label' => 'ATIVO', 'variant' => 'success'],
                    UserStatus::DISABLED => ['label' => 'DESATIVADO', 'variant' => 'muted'],
                };
                $toggleLabel = $user->status === UserStatus::DISABLED ? 'Ativar' : 'Desativar';
            @endphp
            <tr wire:key="user-{{ $user->id }}" class="hover:bg-neutral-800/50">
                <x-admin.td class="font-mono font-bold">#{{ $user->id }}</x-admin.td>
                <x-admin.td class="font-bold text-neutral-100">{{ $user->name }}</x-admin.td>
                <x-admin.td compact class="font-mono">{{ $user->email }}</x-admin.td>
                <x-admin.td compact>
                    <x-admin.badge :variant="$status['variant']">
                        {{ $status['label'] }}
                    </x-admin.badge>
                </x-admin.td>
                <x-admin.td compact class="font-mono font-semibold">
                    {{ $user->product_monitors_count }} {{ $user->product_monitors_count === 1 ? 'produto' : 'produtos' }}
                </x-admin.td>
                <x-admin.td compact class="font-mono text-neutral-400">{{ $user->created_at?->format('d/m/Y') }}</x-admin.td>
                <x-admin.td right class="space-x-2">
                    @if ($user->status === UserStatus::PENDING)
                        <x-admin.button
                            variant="link-accent"
                            wire:click="resendActivation({{ $user->id }})"
                            wire:loading.attr="disabled"
                            wire:target="resendActivation"
                        >Reenviar ativação</x-admin.button>
                    @endif

                    @if ($hasSubscriptionsPage)
                        <x-admin.button
                            variant="link-accent"
                            :href="route('admin.subscriptions.index', ['user' => $user->id])"
                            wire:navigate
                        >Ver Inscrições</x-admin.button>
                    @endif

                    <x-admin.button
                        :variant="$toggleLabel === 'Desativar' ? 'link-danger' : 'link-success'"
                        wire:click="toggleStatus({{ $user->id }})"
                        wire:target="toggleStatus({{ $user->id }})"
                        wire:loading.attr="disabled"
                        wire:confirm="{{ $toggleLabel }} a conta de {{ $user->name }}?"
                        class="disabled:cursor-wait disabled:opacity-50"
                    >{{ $toggleLabel }}</x-admin.button>
                </x-admin.td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-10 px-4 text-center text-neutral-400">Nenhum usuário encontrado.</td>
            </tr>
        @endforelse

        @if ($users->hasPages())
            <x-slot:footer>
                {{ $users->links('pagination.admin') }}
            </x-slot:footer>
        @endif
    </x-admin.table>
</div>

@push('header-actions')
    @if (Route::has('admin.products.create'))
        <x-admin.button :href="route('admin.products.create')" icon="plus">Novo Produto</x-admin.button>
    @endif
@endpush
