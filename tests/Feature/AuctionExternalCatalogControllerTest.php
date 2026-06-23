<?php

use App\Services\ExternalCatalogClient;

test('it returns external auction item references from public catalogs', function () {
    $this->app->instance(ExternalCatalogClient::class, new class extends ExternalCatalogClient {
        public function getJson(string $url, array $query): array
        {
            if (str_contains($url, 'dummyjson.com')) {
                return [
                    'products' => [
                        [
                            'id' => 1,
                            'title' => 'Modern Chair',
                            'description' => 'Comfortable wooden chair.',
                            'category' => 'furniture',
                            'brand' => 'Acme',
                            'price' => 89.99,
                            'thumbnail' => 'https://example.test/chair.jpg',
                            'rating' => 4.5,
                            'stock' => 12,
                        ],
                    ],
                    'total' => 1,
                ];
            }

            return [
                [
                    'id' => 7,
                    'title' => 'Cotton Jacket',
                    'description' => 'Great outerwear for auction reference.',
                    'category' => 'men\'s clothing',
                    'price' => 55.99,
                    'image' => 'https://example.test/jacket.jpg',
                    'rating' => [
                        'rate' => 4.7,
                        'count' => 500,
                    ],
                ],
            ];
        }
    });

    $this->getJson('/api/auction-external-catalog?query=chair&limit=3')
        ->assertOk()
        ->assertJsonPath('query', 'chair')
        ->assertJsonPath('product_references.source', 'DummyJSON Products')
        ->assertJsonPath('product_references.items.0.title', 'Modern Chair')
        ->assertJsonPath('product_references.items.0.reference_price', 89.99)
        ->assertJsonPath('store_product_references.source', 'Fake Store API')
        ->assertJsonPath('store_product_references.items.0.title', 'Cotton Jacket')
        ->assertJsonPath('store_product_references.items.0.reference_price', 55.99)
        ->assertJsonPath('store_product_references.items.0.rating', 4.7)
        ->assertJsonPath('store_product_references.items.0.rating_count', 500);
});

test('it returns unavailable when external product catalogs fail', function () {
    $this->app->instance(ExternalCatalogClient::class, new class extends ExternalCatalogClient {
        public function getJson(string $url, array $query): array
        {
            throw new RuntimeException('External catalog failed.');
        }
    });

    $this->getJson('/api/auction-external-catalog?query=iphone&limit=2')
        ->assertStatus(503)
        ->assertJsonPath('message', 'External product catalogs are currently unavailable.');
});

test('it returns json unavailable when an external call raises a php error', function () {
    $this->app->instance(ExternalCatalogClient::class, new class extends ExternalCatalogClient {
        public function getJson(string $url, array $query): array
        {
            throw new ErrorException('tempnam(): file created in the system temporary directory');
        }
    });

    $this->getJson('/api/auction-external-catalog')
        ->assertStatus(503)
        ->assertJsonPath('message', 'External product catalogs are currently unavailable.');
});
