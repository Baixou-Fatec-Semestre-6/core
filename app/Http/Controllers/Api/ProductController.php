<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Requests\Api\ProductPriceHistoryRequest;
use App\Services\Api\ProductService;
use App\Http\Resources\ProductResource;
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
}
