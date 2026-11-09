<div wire:poll.15s.visible class="space-y-6">
    @if (session()->has('status'))
        <p role="status"
            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-300">
            {{ session('status') }}
        </p>
    @endif

    <section aria-label="Status do monitoramento" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.card class="p-4">
            <span class="text-[10px] font-bold uppercase text-neutral-400">Última coleta registrada</span>
            <div class="mt-1 font-mono text-sm font-bold text-neutral-100">
                {{ $metrics['latestScrapeAt']?->format('d/m/Y H:i') ?? 'Nenhuma coleta' }}
            </div>
            <p class="mt-1 font-mono text-[10px] text-emerald-400">
                {{ number_format($metrics['successfulLastDay'], 0, ',', '.') }} preços coletados nas últimas 24h
                <span class="text-neutral-500">·</span>
                {{ number_format($metrics['failedRequests'], 0, ',', '.') }} solicitações com falha
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

        <x-admin.card class="border-sky-500/30 bg-sky-500/10 p-4">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] font-bold uppercase text-sky-300">Aguardando resultado</span>
                <button
                    type="button"
                    wire:click="$refresh"
                    wire:loading.attr="disabled"
                    wire:target="$refresh"
                    aria-label="Atualizar status das coletas"
                    title="Atualizar agora"
                    class="rounded p-1 text-sky-300 transition hover:bg-sky-400/10 hover:text-sky-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300 disabled:cursor-wait disabled:opacity-50"
                >
                    <x-admin.icon
                        name="refresh"
                        size="h-4 w-4"
                        wire:loading.class="animate-spin"
                        wire:target="$refresh"
                    />
                </button>
            </div>
            <div class="mt-1 font-mono text-sm font-bold text-sky-300">
                {{ number_format($metrics['pendingRequests'], 0, ',', '.') }} {{ $metrics['pendingRequests'] === 1 ? 'solicitação pendente' : 'solicitações pendentes' }}
            </div>
            <p class="mt-1 text-[10px] text-sky-200">Nova verificação a cada 15 segundos.</p>
        </x-admin.card>

        <x-admin.card class="border-red-500/30 bg-red-500/10 p-4">
            <span class="text-[10px] font-bold uppercase text-red-400">Scrapes com falha</span>
            <div class="mt-1 font-mono text-sm font-bold text-red-400">
                {{ number_format($metrics['failedRequests'], 0, ',', '.') }}
                {{ $metrics['failedRequests'] === 1 ? 'tentativa com erro' : 'tentativas com erro' }}
            </div>
            <p class="mt-1 text-[10px] text-red-300">A ação de reenvio aparece somente nos itens com falha.</p>
        </x-admin.card>
    </section>

    <x-admin.card class="space-y-3 p-5">
        @php
            $hasFilters = $this->search !== '' || $this->status !== '' || $this->fromDate !== '' || $this->toDate !== '';
            $resultCount = $scrapeRequests->total();
        @endphp
        <div class="flex flex-col justify-between gap-2 border-b border-neutral-800 pb-2 sm:flex-row sm:items-center">
            <h2 class="text-xs font-bold uppercase text-neutral-100">
                Status das coletas
            </h2>
            <span class="font-mono text-xs text-neutral-400">
                {{ number_format($resultCount, 0, ',', '.') }}
                @if ($hasFilters)
                    {{ $resultCount === 1 ? 'resultado' : 'resultados' }}
                @else
                    {{ $resultCount === 1 ? 'solicitação' : 'solicitações' }}
                    <span class="text-neutral-500">de {{ number_format($totalScrapeRequests, 0, ',', '.') }}</span>
                @endif
            </span>
        </div>

        <div class="flex flex-col gap-3 border-b border-neutral-800 pb-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <x-admin.input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Produto, loja, URL, erro ou ID..."
                    aria-label="Buscar solicitações de scraping"
                    autocomplete="off"
                    class="w-full lg:flex-1"
                />

                <x-admin.select wire:model.live="status" aria-label="Filtrar por status" class="w-full lg:w-52">
                    <option value="">Todos os status</option>
                    <option value="queued">Aguardando resultado</option>
                    <option value="succeeded">Concluído</option>
                    <option value="failed">Falhou</option>
                    <option value="ignored">Ignorado</option>
                </x-admin.select>
            </div>
            @error('status')
                <span role="alert" class="text-xs text-red-400">{{ $message }}</span>
            @enderror

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="flex flex-1 flex-col gap-1 text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                    Solicitado desde
                    <x-admin.input type="date" wire:model.live="fromDate" aria-label="Filtrar solicitações desde esta data" />
                    @error('fromDate')
                        <span role="alert" class="normal-case tracking-normal text-red-400">{{ $message }}</span>
                    @enderror
                </label>

                <label class="flex flex-1 flex-col gap-1 text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                    Solicitado até
                    <x-admin.input type="date" wire:model.live="toDate" aria-label="Filtrar solicitações até esta data" />
                    @error('toDate')
                        <span role="alert" class="normal-case tracking-normal text-red-400">{{ $message }}</span>
                    @enderror
                </label>

                <x-admin.button
                    variant="secondary"
                    wire:click="clearFilters"
                    wire:loading.attr="disabled"
                    wire:target="clearFilters"
                    class="shrink-0"
                >
                    Limpar filtros
                </x-admin.button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table wire:loading.delay.class="opacity-60" wire:target="search,status,fromDate,toDate" class="w-full min-w-[900px] text-left text-xs">
                <thead>
                    <tr class="border-b border-neutral-800 text-[10px] uppercase text-neutral-400">
                        <th class="py-2 pr-4">Job ID</th>
                        <th class="py-2 pr-4">Produto &amp; Loja</th>
                        <th class="py-2 pr-4">URL alvo</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Última atividade</th>
                        <th class="py-2 text-right">Ação do admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800 font-mono">
                    @forelse ($scrapeRequests as $scrapeRequest)
                        <tr wire:key="scrape-request-{{ $scrapeRequest->id }}" class="align-top">
                            <td class="py-3 pr-4 font-bold text-neutral-100"
                                title="{{ $scrapeRequest->request_id }}">
                                {{ substr($scrapeRequest->request_id, 0, 8) }}
                            </td>
                            <td class="py-3 pr-4 font-sans">
                                <span
                                    class="block max-w-[14rem] font-bold text-neutral-100">{{ $scrapeRequest->product_name }}</span>
                                <span
                                    class="text-xs text-neutral-400">{{ $scrapeRequest->store }}</span>
                            </td>
                            <td class="py-3 pr-4">
                                <a href="{{ $scrapeRequest->listing_url }}" target="_blank" rel="noopener noreferrer"
                                    class="block max-w-[16rem] truncate font-sans text-yellow-400 hover:underline"
                                    title="{{ $scrapeRequest->listing_url }}">
                                    {{ $scrapeRequest->listing_url }}
                                </a>
                            </td>
                            <td class="py-3 pr-4 font-sans">
                                @if ($scrapeRequest->status === \App\Enums\ScrapeRequestStatus::QUEUED)
                                    <span
                                        class="rounded-full border border-sky-500/30 bg-sky-500/10 px-2 py-1 text-[10px] font-bold text-sky-300">
                                        Aguardando resultado
                                    </span>
                                    <p class="mt-2 text-neutral-400">Confirmada pelo RabbitMQ.</p>
                                @elseif ($scrapeRequest->status === \App\Enums\ScrapeRequestStatus::SUCCEEDED)
                                    <span
                                        class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-[10px] font-bold text-emerald-300">
                                        Concluído
                                    </span>
                                    <p class="mt-2 text-neutral-400">Preço atualizado com sucesso.</p>
                                @elseif ($scrapeRequest->status === \App\Enums\ScrapeRequestStatus::FAILED)
                                    <span
                                        class="rounded-full border border-red-500/30 bg-red-500/10 px-2 py-1 text-[10px] font-bold text-red-300">
                                        Falhou
                                    </span>
                                    <p class="mt-2 max-w-sm font-semibold text-red-400">
                                        {{ $scrapeRequest->error_reason ?: 'Nenhum detalhe de erro registrado.' }}
                                    </p>
                                @else
                                    <span
                                        class="rounded-full border border-yellow-500/30 bg-yellow-500/10 px-2 py-1 text-[10px] font-bold text-yellow-300">
                                        Ignorado
                                    </span>
                                    <p class="mt-2 max-w-sm text-neutral-400">
                                        {{ $scrapeRequest->error_reason ?: 'O resultado não alterou os dados atuais.' }}
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4 text-neutral-300">
                                {{ ($scrapeRequest->completed_at ?? $scrapeRequest->requested_at)?->format('d/m/Y H:i:s') ?? '-' }}
                            </td>
                            <td class="py-3 text-right">
                                @if (
                                        $scrapeRequest->status === \App\Enums\ScrapeRequestStatus::FAILED
                                        && $scrapeRequest->listing?->status === \App\Enums\ProductListingStatus::FAILED
                                        && $scrapeRequest->listing->product?->track_price
                                    )
                                        <x-admin.button wire:click="retryFailedScrape({{ $scrapeRequest->id }})"
                                            wire:loading.attr="disabled" wire:target="retryFailedScrape({{ $scrapeRequest->id }})"
                                            class="whitespace-nowrap disabled:cursor-wait disabled:opacity-50">
                                            <span wire:loading.remove wire:target="retryFailedScrape({{ $scrapeRequest->id }})">
                                                Reenviar ao RabbitMQ
                                            </span>
                                            <span wire:loading wire:target="retryFailedScrape({{ $scrapeRequest->id }})">
                                                Enviando...
                                            </span>
                                        </x-admin.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center font-sans text-neutral-400">
                                Nenhuma coleta para exibir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($scrapeRequests->hasPages())
            <div class="border-t border-neutral-800 pt-3">
                {{ $scrapeRequests->links('pagination.admin') }}
            </div>
        @endif
    </x-admin.card>
</div>