<div class="space-y-4">
    @if (session()->has('status'))
        <p role="status" class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
            {{ session('status') }}
        </p>
    @endif

    <x-admin.card class="p-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                    Fluxo de alertas
                </span>
                <p class="mt-1 text-xs text-neutral-300">
                    Usuário → Inscrição (Preço-Alvo) → Produto → Queda de preço → Notificação.
                </p>
            </div>

            <div class="text-right text-xs text-neutral-400">
                @if ($selectedUser)
                    <span class="block font-mono text-[10px] text-yellow-400">Filtrando por {{ $selectedUser->name }}</span>
                @else
                    <span class="block font-mono text-[10px]">Total: {{ number_format($subscriptions->total(), 0, ',', '.') }} inscrições</span>
                @endif
                <span class="mt-1 block font-mono text-[10px] text-emerald-300">
                    {{ number_format($totalActive, 0, ',', '.') }} ativas
                </span>
            </div>
        </div>
    </x-admin.card>

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>Sub ID</x-admin.th>
            <x-admin.th>Usuário</x-admin.th>
            <x-admin.th>Produto monitorado</x-admin.th>
            <x-admin.th compact>Preço atual</x-admin.th>
            <x-admin.th compact>Preço-alvo</x-admin.th>
            <x-admin.th compact>Status</x-admin.th>
            <x-admin.th right>Ação admin</x-admin.th>
        </x-slot:head>

        @forelse ($subscriptions as $subscription)
            @php
                $alert = $subscription->alerts->first();
                $latestListing = $subscription->product->listings->first();
                $currentPrice = $latestListing?->current_price;
                $isActive = $alert?->triggered_at === null;
                $status = $isActive
                    ? ['label' => 'ATIVA (AGUARDANDO)', 'variant' => 'success']
                    : ['label' => 'DESATIVADA', 'variant' => 'muted'];
                $toggleLabel = $isActive ? 'Desativar' : 'Ativar';
            @endphp

            <tr wire:key="subscription-{{ $subscription->id }}" class="hover:bg-neutral-800/50">
                <x-admin.td class="font-mono font-bold">#SUB-{{ $subscription->id }}</x-admin.td>
                <x-admin.td class="font-semibold text-neutral-100">
                    {{ $subscription->user?->name }} (#{{ $subscription->user?->id }})
                </x-admin.td>
                <x-admin.td class="font-semibold text-neutral-200">{{ $subscription->product?->name }}</x-admin.td>
                <x-admin.td compact class="font-mono">
                    {{ $currentPrice === null ? '—' : 'R$ '.number_format((float) $currentPrice, 2, ',', '.') }}
                </x-admin.td>
                <x-admin.td compact class="font-mono font-bold text-orange-400">
                    {{ $alert === null ? '—' : 'R$ '.number_format((float) $alert->target_price, 2, ',', '.') }}
                </x-admin.td>
                <x-admin.td compact>
                    <x-admin.badge :variant="$status['variant']">
                        {{ $status['label'] }}
                    </x-admin.badge>
                </x-admin.td>
                <x-admin.td right>
                    <x-admin.button
                        :variant="$isActive ? 'link-danger' : 'link-success'"
                        wire:click="toggleStatus({{ $subscription->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleStatus({{ $subscription->id }})"
                        class="disabled:cursor-wait disabled:opacity-50"
                    >
                        {{ $toggleLabel }}
                    </x-admin.button>
                </x-admin.td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-10 px-4 text-center text-neutral-400">
                    Nenhuma inscrição encontrada.
                </td>
            </tr>
        @endforelse

        @if ($subscriptions->hasPages())
            <x-slot:footer>
                {{ $subscriptions->links('pagination.admin') }}
            </x-slot:footer>
        @endif
    </x-admin.table>
</div>
