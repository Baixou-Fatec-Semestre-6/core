<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Enums\ProductListingStatus;
use App\Enums\ScrapeRequestStatus;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\ScrapeDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Produtos')]
final class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $store = '';

    #[Url]
    public string $monitoring = '';

    public string $productName = '';

    public string $imageUrl = '';

    public array $listingRows = [];

    public string $currency = 'BRL';

    public ?int $selectedListingId = null;

    public ?int $editingProductId = null;

    public ?int $pendingDeleteProductId = null;

    public string $pendingDeleteProductName = '';

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'productName' => 'nome do produto',
            'imageUrl' => 'URL da imagem',
            'currency' => 'moeda dos anúncios',
            'listingRows' => 'lojas e anúncios',
            'listingRows.*.store_id' => 'loja',
            'listingRows.*.path' => 'URL do anúncio',
            'listingRows.*.current_price' => 'preço do anúncio',
        ];
    }

    public function mount(): void
    {
        $this->listingRows = Store::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(static fn (Store $store): array => [
                $store->id => [
                    'store_id' => $store->id,
                    'path' => '',
                    'current_price' => '',
                ],
            ])
            ->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStore(): void
    {
        $this->resetPage();
    }

    public function updatedMonitoring(): void
    {
        $this->resetPage();
    }

    public function clearListingRow(int $storeId): void
    {
        $store = Store::query()->findOrFail($storeId);
        $this->listingRows[$store->id] = [
            'store_id' => $store->id,
            'path' => '',
            'current_price' => '',
        ];

        $this->resetValidation([
            "listingRows.{$store->id}.path",
            "listingRows.{$store->id}.current_price",
        ]);
    }

    public function toggleTracking(int $productId, AuditLogger $auditLogger): void
    {
        Gate::authorize('access-admin');

        $product = Product::query()->findOrFail($productId);
        $product->track_price = ! $product->track_price;
        $product->save();

        $auditLogger->log(
            $product->track_price ? 'product.monitoring_enabled' : 'product.monitoring_disabled',
            $product->track_price
                ? "Monitoramento ativado para o produto #{$product->id}."
                : "Monitoramento desativado para o produto #{$product->id}.",
            $product,
        );
    }

    public function showProductDetails(int $listingId): void
    {
        Gate::authorize('access-admin');

        ProductListing::query()
            ->whereHas('product')
            ->findOrFail($listingId);

        $this->selectedListingId = $listingId;
        $this->dispatch('open-modal', name: 'product-details');
    }

    public function prepareCreateProduct(): void
    {
        Gate::authorize('access-admin');

        $this->editingProductId = null;
        $this->resetProductForm();
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'create-product');
    }

    public function editProduct(int $productId): void
    {
        Gate::authorize('access-admin');

        $product = Product::query()
            ->with('listings')
            ->findOrFail($productId);
        $stores = Store::query()->orderBy('name')->get();

        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->imageUrl = $product->image_url ?? '';
        $this->currency = $product->listings->first()?->currency ?? 'BRL';
        $this->listingRows = $stores
            ->mapWithKeys(function (Store $store) use ($product): array {
                $listing = $product->listings->firstWhere('store_id', $store->id);
                $path = '';

                if ($listing !== null) {
                    $baseUrl = rtrim($store->base_url, '/').'/';
                    $path = str_starts_with($listing->url, $baseUrl)
                        ? substr($listing->url, strlen($baseUrl))
                        : ltrim((string) parse_url($listing->url, PHP_URL_PATH), '/');
                    $query = parse_url($listing->url, PHP_URL_QUERY);

                    if ($query !== null && ! str_contains($path, '?')) {
                        $path .= '?'.$query;
                    }
                }

                return [
                    $store->id => [
                        'store_id' => $store->id,
                        'path' => $path,
                        'current_price' => $listing?->current_price ?? '',
                    ],
                ];
            })
            ->all();

        $this->resetValidation();
        $this->dispatch('open-modal', name: 'create-product');
    }

    public function confirmDeleteProduct(int $productId): void
    {
        Gate::authorize('access-admin');

        $product = Product::query()->findOrFail($productId);
        $this->pendingDeleteProductId = $product->id;
        $this->pendingDeleteProductName = $product->name;
        $this->dispatch('open-modal', name: 'delete-product');
    }

    public function deleteProduct(AuditLogger $auditLogger): void
    {
        Gate::authorize('access-admin');

        abort_if($this->pendingDeleteProductId === null, 404);

        $productId = $this->pendingDeleteProductId;
        $product = Product::query()->findOrFail($productId);
        $selectedListingBelongsToProduct = $this->selectedListingId !== null
            && $product->listings()->whereKey($this->selectedListingId)->exists();

        $product = DB::transaction(function () use ($productId): Product {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            $product->listings()
                ->lockForUpdate()
                ->get()
                ->each(fn (ProductListing $listing) => $listing->delete());
            $product->delete();

            return $product;
        });

        $auditLogger->log(
            'product.deleted',
            "Produto #{$product->id} e seus anúncios foram excluídos.",
            $product,
        );

        if ($selectedListingBelongsToProduct) {
            $this->selectedListingId = null;
            $this->dispatch('close-modal', name: 'product-details');
        }

        if ($this->editingProductId === $product->id) {
            $this->editingProductId = null;
            $this->dispatch('close-modal', name: 'create-product');
        }

        $this->pendingDeleteProductId = null;
        $this->pendingDeleteProductName = '';
        $this->dispatch('close-modal', name: 'delete-product');
        session()->flash('status', 'Produto e anúncios excluídos com sucesso.');
    }

    public function requestScrape(
        int $listingId,
        ScrapeDispatcher $scrapeDispatcher,
        AuditLogger $auditLogger,
    ): void {
        Gate::authorize('access-admin');

        $listing = ProductListing::query()
            ->whereHas('product')
            ->findOrFail($listingId);

        $isRetry = $listing->status === ProductListingStatus::FAILED;
        $wasPublished = $isRetry
            ? $scrapeDispatcher->retry($listing)
            : $scrapeDispatcher->requestIfDue($listing);

        if (! $wasPublished) {
            session()->flash('status', "Anúncio #{$listing->id} já tem uma solicitação de scraping pendente.");

            return;
        }

        if ($isRetry) {
            $auditAction = 'scrape.retry_requested';
            $message = "Nova tentativa de scraping solicitada para o anúncio #{$listing->id}.";
        } else {
            $auditAction = 'scrape.manual_requested';
            $message = "Solicitação de scraping enviada para o anúncio #{$listing->id}.";
        }

        $auditLogger->log($auditAction, $message, $listing);
        session()->flash('status', $message);
    }

    public function saveProduct(AuditLogger $auditLogger): void
    {
        Gate::authorize('access-admin');

        $stores = Store::query()->orderBy('name')->get();
        if ($stores->isEmpty()) {
            throw ValidationException::withMessages([
                'listingRows' => 'Nenhuma loja está cadastrada para receber anúncios.',
            ]);
        }

        $storeIds = $stores->modelKeys();
        $listingRowRules = [
            'listingRows' => [
                'required',
                'array:'.implode(',', $storeIds),
                'min:1',
            ],
        ];
        $listingRowMessages = [];

        foreach ($stores as $store) {
            $path = $this->listingRows[$store->id]['path'] ?? null;
            $hasPath = filled($path);

            $listingRowRules["listingRows.{$store->id}.store_id"] = [
                'required',
                'integer',
                Rule::in([$store->id]),
            ];
            $listingRowRules["listingRows.{$store->id}.path"] = $hasPath
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $listingRowRules["listingRows.{$store->id}.current_price"] = $hasPath
                ? ['required', 'numeric', 'min:0', 'max:99999999.99']
                : ['nullable'];
            $listingRowMessages["listingRows.{$store->id}.current_price.required"] =
                "Informe o preço do anúncio da loja {$store->name}.";
        }

        $validated = $this->validate([
            'productName' => ['required', 'string', 'max:255'],
            'imageUrl' => filled($this->imageUrl) ? ['url:http,https', 'max:255'] : ['nullable'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            ...$listingRowRules,
        ], $listingRowMessages);

        $listingRowsWithPath = array_filter(
            $validated['listingRows'],
            static fn (array $row): bool => filled($row['path']),
        );

        if ($listingRowsWithPath === []) {
            throw ValidationException::withMessages([
                'listingRows' => 'Informe a URL de pelo menos uma loja.',
            ]);
        }

        $storeIds = array_values(array_map(
            static fn (array $row): int => (int) $row['store_id'],
            $validated['listingRows'],
        ));
        $isEditing = $this->editingProductId !== null;

        $product = DB::transaction(function () use ($validated, $storeIds, $isEditing): Product {
            $stores = Store::query()
                ->whereKey($storeIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($stores->count() !== count($storeIds)) {
                throw ValidationException::withMessages([
                    'listingRows' => 'Uma das lojas selecionadas não está mais disponível.',
                ]);
            }

            if ($isEditing) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($this->editingProductId);
                $product->update([
                    'name' => $validated['productName'],
                    'image_url' => $validated['imageUrl'] ?: null,
                ]);
                $existingListings = $product->listings()
                    ->whereIn('store_id', $storeIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('store_id');
            } else {
                $product = Product::query()->create([
                    'name' => $validated['productName'],
                    'image_url' => $validated['imageUrl'] ?: null,
                ]);
                $existingListings = collect();
            }

            foreach ($validated['listingRows'] as $storeId => $row) {
                $store = $stores->get((int) $row['store_id']);

                if (! filled($row['path'])) {
                    $existingListings->get((int) $storeId)?->delete();

                    continue;
                }

                $path = ltrim(trim($row['path']), '/');
                $url = rtrim($store->base_url, '/').($path === '' ? '' : '/'.$path);

                if (strlen($url) > 255) {
                    throw ValidationException::withMessages([
                        "listingRows.{$storeId}.path" => 'O caminho é muito longo para a URL completa desta loja.',
                    ]);
                }

                $listingAttributes = [
                    'store_id' => $store->id,
                    'store' => $store->name,
                    'url' => $url,
                    'current_price' => $row['current_price'],
                    'currency' => $validated['currency'],
                ];
                $existingListing = $existingListings->get((int) $storeId);

                if ($existingListing === null) {
                    $listing = $product->listings()->create($listingAttributes);
                    $listing->priceHistories()->create([
                        'price' => $listing->current_price,
                        'currency' => $listing->currency,
                        'scraped_at' => now(),
                    ]);
                } else {
                    $existingListing->update($listingAttributes);
                }
            }

            return $product;
        });

        $auditLogger->log(
            $isEditing ? 'product.updated' : 'product.created',
            $isEditing
                ? "Produto #{$product->id} atualizado."
                : "Produto #{$product->id} cadastrado.",
            $product,
        );

        $this->editingProductId = null;
        $this->resetProductForm();
        $this->listingRows = $stores
            ->mapWithKeys(static fn (Store $store): array => [
                $store->id => [
                    'store_id' => $store->id,
                    'path' => '',
                    'current_price' => '',
                ],
            ])
            ->all();
        $this->currency = 'BRL';
        $this->dispatch('close-modal', name: 'create-product');
        session()->flash(
            'status',
            $isEditing ? 'Produto atualizado com sucesso.' : 'Produto cadastrado com sucesso.',
        );
    }

    private function resetProductForm(): void
    {
        $this->reset(['productName', 'imageUrl']);
        $this->currency = 'BRL';
        $this->listingRows = Store::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(static fn (Store $store): array => [
                $store->id => [
                    'store_id' => $store->id,
                    'path' => '',
                    'current_price' => '',
                ],
            ])
            ->all();
    }

    public function render(): View
    {
        $searchTerm = trim($this->search);
        $nextScrapeTimestamp = Cache::get('scrape-dispatch:next-run');
        $nextScrapeAt = $nextScrapeTimestamp === null
            ? null
            : Carbon::createFromTimestamp($nextScrapeTimestamp, config('app.timezone'));

        $listings = ProductListing::query()
            ->with(['product', 'storeRecord'])
            ->withExists([
                'scrapeRequests as scrape_request_pending' => fn (Builder $query) => $query
                    ->whereIn('status', [
                        ScrapeRequestStatus::DISPATCHING,
                        ScrapeRequestStatus::QUEUED,
                    ]),
            ])
            ->whereHas('product')
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $like = '%'.addcslashes($searchTerm, '%_\\').'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', $like))
                        ->orWhereHas('storeRecord', fn (Builder $storeQuery) => $storeQuery->where('name', 'like', $like))
                        ->orWhere('store', 'like', $like)
                        ->orWhere('url', 'like', $like);
                });
            })
            ->when($this->store !== '', fn (Builder $query) => $query->where('store_id', $this->store))
            ->when(
                $this->monitoring === 'on',
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery->where('track_price', true),
                ),
            )
            ->when(
                $this->monitoring === 'off',
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery->where('track_price', false),
                ),
            )
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.products.index', [
            'listings' => $listings,
            'nextScrapeAt' => $nextScrapeAt,
            'selectedListing' => $this->selectedListingId === null
                ? null
                : ProductListing::query()
                    ->with([
                        'product',
                        'storeRecord',
                        'priceHistories' => fn ($query) => $query
                            ->orderByDesc('scraped_at')
                            ->limit(20),
                    ])
                    ->withExists([
                        'scrapeRequests as scrape_request_pending' => fn (Builder $query) => $query
                            ->whereIn('status', [
                                ScrapeRequestStatus::DISPATCHING,
                                ScrapeRequestStatus::QUEUED,
                            ]),
                    ])
                    ->whereHas('product')
                    ->findOrFail($this->selectedListingId),
            'stores' => Store::query()->orderBy('name')->get(),
        ]);
    }
}
