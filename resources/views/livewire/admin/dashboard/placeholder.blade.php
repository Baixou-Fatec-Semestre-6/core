<div class="space-y-6" aria-label="Carregando dashboard" aria-busy="true">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach (range(1, 6) as $card)
            <div wire:key="dashboard-placeholder-card-{{ $card }}" class="animate-pulse rounded-xl border border-neutral-800 bg-neutral-900 p-4">
                <div class="h-3 w-2/3 rounded bg-neutral-800"></div>
                <div class="mt-3 h-7 w-1/3 rounded bg-neutral-800"></div>
                <div class="mt-3 h-2 w-3/4 rounded bg-neutral-800"></div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @foreach (range(1, 2) as $panel)
            <div wire:key="dashboard-placeholder-panel-{{ $panel }}" class="animate-pulse space-y-4 rounded-xl border border-neutral-800 bg-neutral-900 p-5">
                <div class="h-4 w-1/2 rounded bg-neutral-800"></div>
                @foreach (range(1, 3) as $row)
                    <div wire:key="dashboard-placeholder-panel-{{ $panel }}-row-{{ $row }}" class="h-14 rounded-lg border border-neutral-800 bg-neutral-800/50"></div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
