{{-- Livewire-aware pagination in the admin theme. Use: {{ $paginator->links('pagination.admin') }} --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="flex items-center justify-between text-xs">
        <p class="text-neutral-400 font-mono">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ number_format($paginator->total(), 0, ',', '.') }}
        </p>

        <div class="flex items-center space-x-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 rounded-lg border border-neutral-800 text-neutral-600 font-semibold">Anterior</span>
            @else
                <x-admin.button variant="secondary" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">Anterior</x-admin.button>
            @endif

            <span class="px-1 font-mono text-neutral-400">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <x-admin.button variant="secondary" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">Próxima</x-admin.button>
            @else
                <span class="px-3 py-2 rounded-lg border border-neutral-800 text-neutral-600 font-semibold">Próxima</span>
            @endif
        </div>
    </nav>
@endif
