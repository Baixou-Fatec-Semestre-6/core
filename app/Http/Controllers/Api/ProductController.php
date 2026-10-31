<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Requests\Api\ProductPriceHistoryRequest;
use App\Http\Requests\Api\TrackProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\Api\ProductService;
use App\Services\PriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly PriceService $priceService,
    ) {
        //
    }

    /**
     * List all products with optional query filter and pagination.
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $products = $this->productService->getAll(
            $validated['q'] ?? null,
            $validated['per_page'] ?? 15,
            $validated['scope'] ?? null,
            $request->user(),
        );

        return ProductResource::collection($products);
    }

    /**
     * Show a specific product by ID.
     */
    public function show(int $id): ProductResource
    {
        $product = $this->productService->getById($id);

        return new ProductResource($product);
    }

    /**
     * Get price history for a specific product.
     */
    public function priceHistory(ProductPriceHistoryRequest $request, int $id): ProductResource
    {
        $product = $this->productService->getPriceHistory($id, $request->period());

        return new ProductResource($product);
    }

    /**
     * Start monitoring every active listing for a product.
     */
    public function track(TrackProductRequest $request): JsonResponse
    {
        $product = $this->productService->track(
            (int) $request->validated('base_product_id'),
            $request->user(),
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Stop monitoring a product and its listings.
     */
    public function untrack(Request $request, int $id): JsonResponse
    {
        $this->productService->untrack($id, $request->user());

        return response()->json(null, 204);
    }

    /**
     * Simulate a price drop  (demo only).
     * 
     * Lowers the product price by 5% and sends e-mail and push notifications to all users tracking it.
     * 
     * @tags Demo
     */
    public function simulatePriceDrop(Request $request, int $id): ProductResource
    {
        abort_unless(config('app.demo_endpoints'), 404);

        $this->priceService->simulatePriceDrop($id);

        return new ProductResource(
            $this->productService->getById($id)
        );
    }
}
