@use('Illuminate\Support\Facades\Route')

<div class="space-y-6">
    <section aria-label="Indicadores do sistema">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            <x-admin.stat
                label="Produtos Totais"
                :value="number_format($metrics['products'], 0, ',', '.')"
                hint="Cadastrados no sistema"
            />
            <x-admin.stat
                label="Produtos Monitorados"
                :value="number_format($metrics['monitoredProducts'], 0, ',', '.')"
                hint="Produtos acompanhados por usuários"
                tone="accent"
            />
            <x-admin.stat
                label="Usuários"
                :value="number_format($metrics['users'], 0, ',', '.')"
                hint="Contas de usuários cadastradas"
            />
            <x-admin.stat
                label="Alertas Ativos"
                :value="number_format($metrics['activeAlerts'], 0, ',', '.')"
                hint="Aguardando atingir o preço-alvo"
                tone="success"
            />
            <x-admin.stat
                label="Atualizações (24h)"
                :value="number_format($metrics['priceUpdates'], 0, ',', '.')"
                hint="Preços coletados nas últimas 24 horas"
                tone="success"
            />
            <x-admin.stat
                label="Falhas de Scraping"
                :value="number_format($metrics['failedRequests'], 0, ',', '.')"
                hint="Solicitações de scraping com erro"
                tone="danger"
            />
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2" aria-label="Atividade recente">
        <x-admin.card class="p-5">
            <div class="mb-4 flex items-start justify-between gap-4 border-b border-neutral-800 pb-3">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-100">
                        Últimos preços coletados
                    </h2>
                    <p class="mt-1 text-[11px] text-neutral-400">Atividade recente por produto e loja</p>
                </div>
                <span class="shrink-0 font-mono text-[11px] text-neutral-400">
                    Últimas 5 coletas
                </span>
            </div>

            <div class="space-y-2.5">
                @forelse ($recentPriceUpdates as $update)
                    <article
                        wire:key="price-update-{{ $update->id }}"
                        class="flex flex-col justify-between gap-3 rounded-lg border border-neutral-800 bg-neutral-800/50 p-3 sm:flex-row sm:items-center"
                    >
                        <div class="min-w-0">
                            <h3 class="truncate text-xs font-bold text-neutral-100">
                                {{ $update->listing->product->name }}
                            </h3>
                            <p class="mt-1 text-[11px] text-neutral-400">
                                {{ $update->listing->store }}
                                <span aria-hidden="true">·</span>
                                {{ $update->scraped_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="shrink-0 font-mono text-xs font-bold text-emerald-400">
                            {{ $update->currency }} {{ number_format((float) $update->price, 2, ',', '.') }}
                        </span>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-neutral-400">
                        Nenhuma atualização de preço registrada.
                    </p>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card class="p-5">
            <div class="mb-4 flex items-start justify-between gap-4 border-b border-neutral-800 pb-3">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-red-400">
                        Falhas de scraping recentes
                    </h2>
                    <p class="mt-1 text-[11px] text-neutral-400">Solicitações que precisam de atenção</p>
                </div>
                <x-admin.badge variant="count-danger">
                    {{ number_format($metrics['failedRequests'], 0, ',', '.') }}
                </x-admin.badge>
            </div>

            <div class="space-y-3">
                @forelse ($recentFailedRequests as $scrapeRequest)
                    <article
                        wire:key="failed-scrape-request-{{ $scrapeRequest->id }}"
                        class="rounded-lg border border-red-500/30 bg-red-500/10 p-3"
                    >
                        <span class="block font-mono text-[10px] font-bold uppercase text-red-300">
                            Solicitação {{ substr($scrapeRequest->request_id, 0, 8) }}
                            <span aria-hidden="true">·</span> {{ $scrapeRequest->store }}
                        </span>
                        <h3 class="mt-1 text-xs font-bold text-neutral-100">
                            {{ $scrapeRequest->product_name }}
                        </h3>
                        <p class="mt-1 text-[11px] text-red-300">
                            Erro: {{ $scrapeRequest->error_reason ?: 'Nenhum detalhe registrado.' }}
                        </p>
                        <p class="mt-1 font-mono text-[10px] text-neutral-400">
                            Atualizado em {{ $scrapeRequest->completed_at?->format('d/m/Y H:i') }}
                        </p>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-neutral-400">
                        Nenhuma falha de scraping registrada.
                    </p>
                @endforelse
            </div>
        </x-admin.card>
    </section>
</div>

@push('header-actions')
    @if (Route::has('admin.products.create'))
        <x-admin.button :href="route('admin.products.create')" icon="plus">Novo Produto</x-admin.button>
    @endif
@endpush
