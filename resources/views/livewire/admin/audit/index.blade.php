<div class="space-y-4">
    <x-admin.card padded class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-xs font-bold uppercase text-neutral-100">
                Log de Auditoria de Ações Administrativas
            </h2>
            <p class="text-[11px] text-neutral-400">
                Rastreabilidade das modificações realizadas pelos administradores
            </p>
        </div>

        <div class="flex items-center gap-3 text-xs font-mono text-neutral-400">
            <span wire:loading.delay class="text-yellow-400">Atualizando…</span>
            <span>Total: {{ number_format($auditLogs->total(), 0, ',', '.') }}</span>
        </div>
    </x-admin.card>

    <x-admin.table wire:loading.delay.class="opacity-60">
        <x-slot:head>
            <x-admin.th>Data / Hora</x-admin.th>
            <x-admin.th>Administrador</x-admin.th>
            <x-admin.th>Mensagem de Auditoria (Ação Executada)</x-admin.th>
            <x-admin.th right>IP de Origem</x-admin.th>
        </x-slot:head>

        @forelse ($auditLogs as $auditLog)
            <tr wire:key="audit-log-{{ $auditLog->getKey() }}" class="font-mono text-neutral-300">
                <x-admin.td class="whitespace-nowrap text-neutral-400">
                    {{ $auditLog->created_at?->format('Y-m-d H:i:s') ?? '—' }}
                </x-admin.td>
                <x-admin.td class="whitespace-nowrap font-bold text-neutral-100">
                    {{ $auditLog->admin_name ?? '—' }}
                </x-admin.td>
                <x-admin.td class="min-w-72 font-sans font-medium text-neutral-200">
                    <span class="block">{{ $auditLog->message ?? '—' }}</span>
                    @if ($auditLog->action)
                        <span class="mt-1 block font-mono text-[10px] text-neutral-400">
                            {{ $auditLog->action }}
                            @if ($auditLog->subject_type && $auditLog->subject_id)
                                · {{ class_basename($auditLog->subject_type) }} #{{ $auditLog->subject_id }}
                            @endif
                        </span>
                    @endif
                </x-admin.td>
                <x-admin.td right class="whitespace-nowrap text-neutral-400">
                    {{ $auditLog->ip ?? '—' }}
                </x-admin.td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-10 px-4 text-center text-neutral-400">
                    Nenhum registro de auditoria encontrado.
                </td>
            </tr>
        @endforelse

        @if ($auditLogs->hasPages())
            <x-slot:footer>
                {{ $auditLogs->links('pagination.admin') }}
            </x-slot:footer>
        @endif
    </x-admin.table>
</div>
