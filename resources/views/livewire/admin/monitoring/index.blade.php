<div class="space-y-6">
    @if (session()->has('status'))
        <p role="status"
            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
            {{ session('status') }}
        </p>
    @endif

    <section aria-label="Status do monitoramento" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <x-admin.card class="p-4">
            <span class="text-[10px] font-bold uppercase text-neutral-400">Última coleta registrada</span>
            <div class="mt-1 font-mono text-sm font-bold text-neutral-100">
                {{ $metrics['latestScrapeAt']?->format('d/m/Y H:i') ?? 'Nenhuma coleta' }}
            </div>
            <p class="mt-1 font-mono text-[10px] text-emerald-400">
                {{ number_format($metrics['successfulLastDay'], 0, ',', '.') }} preços coletados nas últimas 24h
                <span class="text-neutral-500">·</span>
                {{ number_format($metrics['failedListings'], 0, ',', '.') }} falhas ativas
            </p>
        </x-admin.card>

        <x-admin.card class="p-4">
            <span class="text-[10px] font-bold uppercase text-neutral-400">Próxima varredura agendada</span>
            <div class="mt-1 font-mono text-sm font-bold text-yellow-400">
                {{ $metrics['nextScrapeAt']?->format('d/m/Y H:i') ?? '-' }}
            </div>
            <p class="mt-1 text-[10px] text-neutral-400">
                Executa automaticamente em intervalos regulares.
            </p>
        </x-admin.card>

        <x-admin.card class="border-red-500/30 bg-red-500/10 p-4">
            <span class="text-[10px] font-bold uppercase text-red-400">Scrapes com falha</span>
            <div class="mt-1 font-mono text-sm font-bold text-red-400">
                {{ number_format($metrics['failedListings'], 0, ',', '.') }}
                {{ $metrics['failedListings'] === 1 ? 'item com erro' : 'itens com erro' }}
            </div>
            <p class="mt-1 text-[10px] text-red-300">Reenvie uma coleta pela fila abaixo.</p>
        </x-admin.card>
    </section>

    <x-admin.card class="space-y-3 p-5">
        <div class="flex flex-col justify-between gap-2 border-b border-neutral-800 pb-2 sm:flex-row sm:items-center">
            <h2 class="text-xs font-bold uppercase text-neutral-100">
                Fila de falhas e reprocessamento
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-xs">
                <thead>
                    <tr class="border-b border-neutral-800 text-[10px] uppercase text-neutral-400">
                        <th class="py-2 pr-4">Job ID</th>
                        <th class="py-2 pr-4">Produto &amp; Loja</th>
                        <th class="py-2 pr-4">URL alvo</th>
                        <th class="py-2 pr-4">Erro detectado</th>
                        <th class="py-2 pr-4">Tentativas</th>
                        <th class="py-2 text-right">Ação do admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800 font-mono">
                    @forelse ($failedListings as $listing)
                        <tr wire:key="failed-scrape-{{ $listing->id }}" class="align-top">
                            <td class="py-3 pr-4 font-bold text-neutral-100">#{{ $listing->id }}</td>
                            <td class="py-3 pr-4 font-sans">
                                <span
                                    class="block max-w-[14rem] font-bold text-neutral-100">{{ $listing->product->name }}</span>
                                <span
                                    class="text-xs text-neutral-400">{{ $listing->storeRecord?->name ?? $listing->store }}</span>
                            </td>
                            <td class="py-3 pr-4">
                                <a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer"
                                    class="block max-w-[16rem] truncate font-sans text-yellow-400 hover:underline"
                                    title="{{ $listing->url }}">
                                    {{ $listing->url }}
                                </a>
                            </td>
                            <td class="max-w-sm py-3 pr-4 font-sans font-semibold text-red-400">
                                {{ $listing->last_error_reason ?: 'Nenhum detalhe de erro registrado.' }}
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4 text-neutral-300">
                                {{ min($listing->scrape_attempts, $metrics['maxAttempts']) }} /
                                {{ $metrics['maxAttempts'] }}
                            </td>
                            <td class="py-3 text-right">
                                @if ($listing->product->track_price)
                                    <x-admin.button wire:click="retryFailedScrape({{ $listing->id }})"
                                        wire:loading.attr="disabled" wire:target="retryFailedScrape({{ $listing->id }})"
                                        class="whitespace-nowrap disabled:cursor-wait disabled:opacity-50">
                                        <span wire:loading.remove wire:target="retryFailedScrape({{ $listing->id }})">
                                            Reenviar ao RabbitMQ
                                        </span>
                                        <span wire:loading wire:target="retryFailedScrape({{ $listing->id }})">
                                            Enviando...
                                        </span>
                                    </x-admin.button>
                                @else
                                    <span class="text-[10px] text-neutral-500">Monitoramento pausado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center font-sans text-neutral-400">
                                Nenhum scrape com falha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($failedListings->hasPages())
            <div class="border-t border-neutral-800 pt-3">
                {{ $failedListings->links('pagination.admin') }}
            </div>
        @endif
    </x-admin.card>
</div>