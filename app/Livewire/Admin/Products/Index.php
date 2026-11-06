<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    public function toggleTracking(int $productId): void
    {
        Gate::authorize('access-admin');

        $product = Product::query()->findOrFail($productId);
        $product->track_price = ! $product->track_price;
        $product->save();
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

    public function createProduct(): void
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
        }

        $validated = $this->validate([
            'productName' => ['required', 'string', 'max:255'],
            'imageUrl' => filled($this->imageUrl) ? ['url:http,https', 'max:255'] : ['nullable'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            ...$listingRowRules,
        ]);

        $validated['listingRows'] = array_filter(
            $validated['listingRows'],
            static fn (array $row): bool => filled($row['path']),
        );

        if ($validated['listingRows'] === []) {
            throw ValidationException::withMessages([
                'listingRows' => 'Informe a URL de pelo menos uma loja.',
            ]);
        }

        $storeIds = array_values(array_map(
            static fn (array $row): int => (int) $row['store_id'],
            $validated['listingRows'],
        ));

        DB::transaction(function () use ($validated, $storeIds): void {
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

            $product = Product::query()->create([
                'name' => $validated['productName'],
                'image_url' => $validated['imageUrl'] ?: null,
            ]);

            foreach ($validated['listingRows'] as $storeId => $row) {
                $store = $stores->get((int) $row['store_id']);
                $path = ltrim(trim($row['path']), '/');
                $url = rtrim($store->base_url, '/').($path === '' ? '' : '/'.$path);

                if (strlen($url) > 255) {
                    throw ValidationException::withMessages([
                        "listingRows.{$storeId}.path" => 'O caminho é muito longo para a URL completa desta loja.',
                    ]);
                }

                $product->listings()->create([
                    'store_id' => $store->id,
                    'store' => $store->name,
                    'url' => $url,
                    'current_price' => $row['current_price'],
                    'currency' => $validated['currency'],
                ]);
            }
        });

        $this->reset(['productName', 'imageUrl']);
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
        session()->flash('status', 'Produto cadastrado com sucesso.');
    }

    public function render(): View
    {
        $searchTerm = trim($this->search);

        $listings = ProductListing::query()
            ->with(['product', 'storeRecord'])
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
                    ->whereHas('product')
                    ->findOrFail($this->selectedListingId),
            'stores' => Store::query()->orderBy('name')->get(),
        ]);
    }
}
