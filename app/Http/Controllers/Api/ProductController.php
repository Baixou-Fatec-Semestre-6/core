<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Requests\Api\ProductPriceHistoryRequest;
use App\Http\Requests\Api\TrackProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\Api\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        //
    }

    /**
     * List all products with optional query filter and pagination.
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 15);
        $products = $this->productService->getAll($request->query('q'), $perPage);

        return ProductResource::collection($products);
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
}
