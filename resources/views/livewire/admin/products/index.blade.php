<div class="space-y-4">
    @if (session()->has('status'))
        <p role="status" class="text-sm text-emerald-300">{{ session('status') }}</p>
    @endif

    <x-admin.card padded class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex min-w-0 flex-col gap-3 md:flex-1 md:flex-row md:items-center">
            <x-admin.input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nome, loja ou URL..."
                aria-label="Buscar produtos por nome, loja ou URL"
                autocomplete="off"
                class="w-full md:max-w-md"
            />

            <x-admin.select wire:model.live="store" aria-label="Filtrar por loja" class="w-full md:w-48">
                <option value="">Todas as lojas</option>
                @foreach ($stores as $availableStore)
                    <option wire:key="store-filter-{{ $availableStore->id }}" value="{{ $availableStore->id }}">
                        {{ $availableStore->name }}
                    </option>
                @endforeach
            </x-admin.select>

            <x-admin.select wire:model.live="monitoring" aria-label="Filtrar por monitoramento" class="w-full md:w-48">
                <option value="">Todos os status</option>
                <option value="on">Monitoramento ativo</option>
                <option value="off">Monitoramento pausado</option>
            </x-admin.select>
        </div>

        <x-admin.button
            x-on:click="$dispatch('open-modal', 'create-product')"
            icon="plus"
            class="shrink-0"
        >
            Cadastrar Produto
        </x-admin.button>
    </x-admin.card>

    <x-admin.table wire:loading.delay.class="opacity-60" wire:target="search,store,monitoring">
        <x-slot:head>
            <x-admin.th>ID</x-admin.th>
            <x-admin.th>Nome do Produto</x-admin.th>
            <x-admin.th compact>Loja</x-admin.th>
            <x-admin.th compact>Preço Atual</x-admin.th>
            <x-admin.th compact>Monitoramento</x-admin.th>
            <x-admin.th compact>Último Scrape</x-admin.th>
            <x-admin.th compact>Próximo Scrape</x-admin.th>
        </x-slot:head>

        @forelse ($listings as $listing)
            @php
    $isTracking = $listing->product->track_price;
    $nextScrapeAt = $listing->last_scraped_at?->copy()->addHour();
    $scrapeWasRecentlyRequested = $listing->scrape_requested_at?->isAfter(now()->subMinutes(10)) ?? false;
    $scrapeIsDue = $nextScrapeAt === null || $nextScrapeAt->isPast();
    $currencyLabel = $listing->currency === 'BRL' ? 'R$' : $listing->currency;
            @endphp
            <tr wire:key="listing-{{ $listing->id }}" class="hover:bg-neutral-800/50">
                <x-admin.td class="font-mono font-bold">#{{ $listing->product_id }}</x-admin.td>
                <x-admin.td>
                    <button
                        type="button"
                        wire:click="showProductDetails({{ $listing->id }})"
                        class="cursor-pointer text-left font-semibold text-yellow-400 hover:text-yellow-300 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-400"
                        aria-label="Ver detalhes de {{ $listing->product->name }}"
                    >
                        {{ $listing->product->name }}
                    </button>
                </x-admin.td>
                <x-admin.td compact>
                    <span class="rounded border border-neutral-700 bg-neutral-800 px-2 py-0.5 text-[10px] font-semibold text-neutral-200">
                        {{ $listing->storeRecord?->name ?? $listing->store }}
                    </span>
                </x-admin.td>
                <x-admin.td compact class="font-mono font-bold text-emerald-400">
                    {{ $currencyLabel }} {{ number_format((float) $listing->current_price, 2, ',', '.') }}
                </x-admin.td>
                <x-admin.td compact>
                    <button
                        type="button"
                        wire:click="toggleTracking({{ $listing->product_id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleTracking({{ $listing->product_id }})"
                        aria-label="{{ $isTracking ? 'Pausar' : 'Ativar' }} monitoramento de {{ $listing->product->name }}"
                        aria-pressed="{{ $isTracking ? 'true' : 'false' }}"
                        class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold transition-colors disabled:cursor-wait disabled:opacity-50 {{ $isTracking ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-300' : 'border-neutral-700 bg-neutral-700 text-neutral-300' }}"
                    >
                        {{ $isTracking ? 'ON' : 'OFF' }}
                    </button>
                </x-admin.td>
                <x-admin.td compact class="whitespace-nowrap font-mono text-[11px] text-neutral-400">
                    {{ $listing->last_scraped_at?->format('d/m/Y H:i') ?? 'Nunca' }}
                </x-admin.td>
                <x-admin.td compact class="whitespace-nowrap font-mono text-[11px] text-neutral-400">
                    @if (! $isTracking)
                        Pausado
                    @elseif ($scrapeWasRecentlyRequested)
                        Na fila
                    @elseif ($scrapeIsDue)
                        Aguardando
                    @else
                        {{ $nextScrapeAt->format('d/m/Y H:i') }}
                    @endif
                </x-admin.td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-neutral-400">
                    Nenhum produto encontrado.
                </td>
            </tr>
        @endforelse

        @if ($listings->hasPages())
            <x-slot:footer>
                {{ $listings->links('pagination.admin') }}
            </x-slot:footer>
        @endif
    </x-admin.table>

    <x-admin.modal name="product-details" title="Detalhes do Produto & Scraping" max-width="max-w-xl">
        @if ($selectedListing)
            @php
    $selectedCurrency = $selectedListing->currency === 'BRL' ? 'R$' : $selectedListing->currency;
    $selectedStatus = match ($selectedListing->status) {
        \App\Enums\ProductListingStatus::SCRAPED => ['label' => 'Coletado', 'variant' => 'success'],
        \App\Enums\ProductListingStatus::FAILED => ['label' => 'Falha', 'variant' => 'pending'],
        \App\Enums\ProductListingStatus::PENDING => ['label' => 'Pendente', 'variant' => 'muted'],
    };
    $selectedNextScrapeAt = $selectedListing->last_scraped_at?->copy()->addHour();
    $selectedScrapeWasRequested = $selectedListing->scrape_requested_at?->isAfter(now()->subMinutes(10)) ?? false;
    $selectedUrlScheme = strtolower((string) parse_url($selectedListing->url, PHP_URL_SCHEME));
            @endphp

            <div class="max-h-[75vh] space-y-5 overflow-y-auto p-5 text-xs">
                <section class="space-y-3 border-b border-neutral-800 pb-4">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                        Informações do Produto
                    </h4>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <span class="block text-neutral-400">Nome</span>
                            <strong class="text-sm text-neutral-100">{{ $selectedListing->product->name }}</strong>
                        </div>
                        <div>
                            <span class="block text-neutral-400">Loja</span>
                            <strong class="text-neutral-100">
                                {{ $selectedListing->storeRecord?->name ?? $selectedListing->store }}
                            </strong>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <span class="block text-neutral-400">URL de Scraping</span>
                        @if (in_array($selectedUrlScheme, ['http', 'https'], true))
                            <a
                                href="{{ $selectedListing->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block truncate font-mono text-yellow-400 hover:underline"
                            >
                                {{ $selectedListing->url }}
                            </a>
                        @else
                            <span class="break-all font-mono text-neutral-300">{{ $selectedListing->url }}</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <span class="block text-neutral-400">Preço Atual</span>
                            <strong class="font-mono text-base font-black text-emerald-400">
                                {{ $selectedCurrency }} {{ number_format((float) $selectedListing->current_price, 2, ',', '.') }}
                            </strong>
                        </div>
                        <div>
                            <span class="block text-neutral-400">Monitoramento</span>
                            <x-admin.badge :variant="$selectedListing->product->track_price ? 'success' : 'muted'">
                                {{ $selectedListing->product->track_price ? 'ON' : 'OFF' }}
                            </x-admin.badge>
                        </div>
                        <div>
                            <span class="block text-neutral-400">Status do Scraping</span>
                            <x-admin.badge :variant="$selectedStatus['variant']">
                                {{ $selectedStatus['label'] }}
                            </x-admin.badge>
                        </div>
                    </div>

                    @if ($selectedListing->last_error_reason)
                        <p class="rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-red-300">
                            Erro: {{ $selectedListing->last_error_reason }}
                        </p>
                    @endif
                </section>

                <section class="space-y-3 border-b border-neutral-800 pb-4">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                        Cronograma de Scraping
                    </h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded border border-neutral-800 bg-neutral-800/50 p-3 font-mono">
                            <span class="block text-[10px] uppercase text-neutral-400">Último Scrape</span>
                            <span class="font-bold text-neutral-200">
                                {{ $selectedListing->last_scraped_at?->format('d/m/Y H:i') ?? 'Nunca' }}
                            </span>
                        </div>
                        <div class="rounded border border-neutral-800 bg-neutral-800/50 p-3 font-mono">
                            <span class="block text-[10px] uppercase text-neutral-400">Próximo Scrape</span>
                            <span class="font-bold {{ $selectedListing->product->track_price ? 'text-yellow-400' : 'text-neutral-400' }}">
                                @if (! $selectedListing->product->track_price)
                                    Pausado
                                @elseif ($selectedScrapeWasRequested)
                                    Na fila
                                @elseif ($selectedNextScrapeAt === null || $selectedNextScrapeAt->isPast())
                                    Aguardando
                                @else
                                    {{ $selectedNextScrapeAt->format('d/m/Y H:i') }}
                                @endif
                            </span>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                        Histórico de Preços
                    </h4>
                    <div class="max-h-48 overflow-y-auto rounded-lg border border-neutral-800">
                        <table class="w-full text-left font-mono text-[11px]">
                            <thead class="sticky top-0 border-b border-neutral-800 bg-neutral-800/90 text-[10px] text-neutral-400">
                                <tr>
                                    <th class="p-2">Data/Hora</th>
                                    <th class="p-2">Preço Coletado</th>
                                    <th class="p-2">Variação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-800">
                                @forelse ($selectedListing->priceHistories as $priceHistory)
                                    @php
        $olderPriceHistory = $selectedListing->priceHistories->get($loop->index + 1);
        $priceChange = $olderPriceHistory === null
            ? '—'
            : ((float) $priceHistory->price < (float) $olderPriceHistory->price
                ? 'Queda'
                : ((float) $priceHistory->price > (float) $olderPriceHistory->price ? 'Alta' : 'Estável'));
        $priceChangeClass = $priceChange === 'Queda'
            ? 'text-emerald-400'
            : ($priceChange === 'Alta' ? 'text-red-400' : 'text-neutral-400');
                                    @endphp
                                    <tr wire:key="product-price-history-{{ $priceHistory->id }}">
                                        <td class="whitespace-nowrap p-2 text-neutral-300">
                                            {{ $priceHistory->scraped_at?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap p-2 font-bold text-neutral-200">
                                            {{ $priceHistory->currency === 'BRL' ? 'R$' : $priceHistory->currency }}
                                            {{ number_format((float) $priceHistory->price, 2, ',', '.') }}
                                        </td>
                                        <td class="p-2 {{ $priceChangeClass }}">{{ $priceChange }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-neutral-400">
                                            Nenhum histórico de preço registrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="flex justify-end border-t border-neutral-800 bg-neutral-800/50 p-4">
                <x-admin.button
                    variant="secondary"
                    x-on:click="$dispatch('close-modal', 'product-details')"
                >
                    Fechar
                </x-admin.button>
            </div>
        @endif
    </x-admin.modal>

    <x-admin.modal
        name="create-product"
        title="Cadastrar Produto"
        icon="plus"
        max-width="max-w-2xl"
        class="max-h-[90vh] flex flex-col"
    >
        <form wire:submit="createProduct" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-admin.label for="product-name">Nome do produto</x-admin.label>
                    <x-admin.input
                        id="product-name"
                        type="text"
                        wire:model="productName"
                        autocomplete="off"
                        required
                        class="w-full"
                    />
                    @error('productName')
                        <p role="alert" class="mt-1 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <x-admin.label for="product-image-url">URL da imagem (opcional)</x-admin.label>
                    <x-admin.input
                        id="product-image-url"
                        type="url"
                        wire:model="imageUrl"
                        autocomplete="url"
                        class="w-full"
                    />
                    @error('imageUrl')
                        <p role="alert" class="mt-1 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <section class="space-y-3 md:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-xs font-bold text-neutral-200">Lojas e anúncios</h4>
                            <p class="mt-1 text-[11px] text-neutral-400">
                                Preencha a URL e o preço das lojas desejadas. As lojas sem URL serão ignoradas.
                            </p>
                        </div>
                    </div>

                    @error('listingRows')
                        <p role="alert" class="text-xs text-red-300">{{ $message }}</p>
                    @enderror

                    @if ($stores->isEmpty())
                        <p role="status" class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-200">
                            Nenhuma loja cadastrada. Execute o seeder de loja s para habilitar o cadastro de anúncios.
                        </p>
                    @endif

                    <div class="max-h-[35vh] space-y-3 overflow-y-auto overscroll-contain pr-2">
                        @foreach ($stores as $availableStore)
                                                    @php
                            $row = $listingRows[$availableStore->id] ?? ['path' => '', 'current_price' => ''];
                                                    @endphp
                                                    <div
                                                        wire:key="new-listing-row-{{ $availableStore->id }}"
                                                        class="grid grid-cols-1 items-start gap-3 rounded-lg border border-neutral-800 bg-neutral-800/40 p-3 lg:grid-cols-[minmax(8rem,0.6fr)_minmax(12rem,1.4fr)_8rem]"
                                                    >
                                                        <div>
                                                            <span class="block text-xs font-semibold text-neutral-200">{{ $availableStore->name }}</span>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <x-admin.label for="listing-path-{{ $availableStore->id }}">URL do anúncio</x-admin.label>
                                                            <div class="flex min-w-0 items-center overflow-hidden rounded-lg border border-neutral-700 bg-neutral-900 focus-within:border-yellow-400">
                                                                <span class="max-w-[45%] truncate border-r border-neutral-700 px-2 py-2 font-mono text-[11px] text-neutral-400">
                                                                    {{ rtrim($availableStore->base_url, '/') }}/
                                                                </span>
                                                                <input
                                                                    id="listing-path-{{ $availableStore->id }}"
                                                                    type="text"
                                                                    wire:model="listingRows.{{ $availableStore->id }}.path"
                                                                    placeholder="caminho/do/produto"
                                                                    autocomplete="off"
                                                                    aria-label="Caminho do produto na loja {{ $availableStore->name }}"
                                                                    class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-xs text-neutral-100 placeholder:text-neutral-500 focus:outline-hidden"
                                                                />
                                                                <button
                                                                    type="button"
                                                                    wire:click="clearListingRow({{ $availableStore->id }})"
                                                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center text-neutral-400 transition-colors hover:text-red-300"
                                                                    aria-label="Limpar URL e preço de {{ $availableStore->name }}"
                                                                    title="Limpar URL e preço"
                                                                >
                                                                    <span aria-hidden="true">×</span>
                                                                </button>
                                                            </div>
                                                            @error("listingRows.{$availableStore->id}.path")
                                                                <p role="alert" class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                                            @enderror
                                                        </div>

                                                        <div>
                                                            <x-admin.label for="listing-price-{{ $availableStore->id }}">Preço (R$)</x-admin.label>
                                                            <x-admin.input
                                                                id="listing-price-{{ $availableStore->id }}"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                wire:model="listingRows.{{ $availableStore->id }}.current_price"
                                                                class="w-full"
                                                            />
                                                            @error("listingRows.{$availableStore->id}.current_price")
                                                                <p role="alert" class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                    </div>
                        @endforeach
                    </div>
                </section>

                <div class="md:col-span-2">
                    <x-admin.label for="listing-currency">Moeda dos anúncios (código ISO)</x-admin.label>
                    <x-admin.input
                        id="listing-currency"
                        type="text"
                        wire:model="currency"
                        maxlength="3"
                        required
                        class="w-full uppercase"
                    />
                    @error('currency')
                        <p role="alert" class="mt-1 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-neutral-800 pt-4">
                <x-admin.button
                    variant="secondary"
                    x-on:click="$dispatch('close-modal', 'create-product')"
                >
                    Cancelar
                </x-admin.button>
                <x-admin.button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="createProduct"
                >
                    <span wire:loading.remove wire:target="createProduct">Salvar Produto</span>
                    <span wire:loading wire:target="createProduct">Salvando...</span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
