<?php

namespace App\Http\Controllers;

use App\Services\ExternalCatalogClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuctionExternalCatalogController extends Controller
{
    public function __construct(private readonly ExternalCatalogClient $client) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['sometimes', 'string', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $query = $validated['query'] ?? 'chair';
        $limit = (int) ($validated['limit'] ?? 5);

        try {
            $products = $this->client->getJson('https://dummyjson.com/products/search', [
                'q' => $query,
                'limit' => $limit,
                'select' => 'id,title,description,category,brand,price,thumbnail,rating,stock',
            ]);

            $storeProducts = $this->client->getJson('https://fakestoreapi.com/products', [
                'limit' => $limit,
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'External product catalogs are currently unavailable.',
            ], 503);
        }

        return response()->json([
            'query' => $query,
            'product_references' => [
                'source' => 'DummyJSON Products',
                'total' => $products['total'] ?? 0,
                'items' => collect($products['products'] ?? [])
                    ->map(fn(array $product): array => [
                        'external_id' => $product['id'] ?? null,
                        'title' => $product['title'] ?? null,
                        'description' => $product['description'] ?? null,
                        'category' => $product['category'] ?? null,
                        'brand' => $product['brand'] ?? null,
                        'reference_price' => $product['price'] ?? null,
                        'rating' => $product['rating'] ?? null,
                        'stock' => $product['stock'] ?? null,
                        'image_url' => $product['thumbnail'] ?? null,
                    ])
                    ->values(),
            ],
            'store_product_references' => [
                'source' => 'Fake Store API',
                'total' => count($storeProducts),
                'items' => collect($storeProducts)
                    ->map(fn(array $product): array => [
                        'external_id' => $product['id'] ?? null,
                        'title' => $product['title'] ?? null,
                        'description' => $product['description'] ?? null,
                        'category' => $product['category'] ?? null,
                        'reference_price' => $product['price'] ?? null,
                        'rating' => $product['rating']['rate'] ?? null,
                        'rating_count' => $product['rating']['count'] ?? null,
                        'image_url' => $product['image'] ?? null,
                    ])
                    ->values(),
            ],
        ]);
    }
}
