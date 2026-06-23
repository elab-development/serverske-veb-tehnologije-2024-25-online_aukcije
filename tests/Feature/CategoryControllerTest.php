<?php

use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it lists all categories', function () {
    Category::factory()->count(2)->create();

    $response = $this->getJson('/api/categories');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'categories')
        ->assertJsonPath('count', 2);
});

test('only admins can create categories', function () {
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller, 'sanctum')
        ->postJson('/api/categories', [
            'name' => 'Satovi',
            'description' => 'Rucni i dzepni satovi na aukciji.',
        ])
        ->assertForbidden();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/categories', [
            'name' => 'Satovi',
            'description' => 'Rucni i dzepni satovi na aukciji.',
        ])
        ->assertCreated()
        ->assertJsonPath('category.name', 'Satovi');
});

test('unauthenticated users cannot create categories', function () {
    $this->postJson('/api/categories', [
        'name' => 'Satovi',
        'description' => 'Rucni i dzepni satovi na aukciji.',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('it returns json when a category is not found', function () {
    $this->getJson('/api/categories/999')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});

test('it lists auctions for a category', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $seller = User::factory()->create(['role' => 'seller']);

    Auction::factory()->create([
        'category_id' => $category->id,
        'user_id' => $seller->id,
        'title' => 'Laptop aukcija',
        'status' => 'active',
        'starting_price' => 300,
    ]);

    Auction::factory()->create([
        'category_id' => $category->id,
        'user_id' => $seller->id,
        'title' => 'Telefon aukcija',
        'status' => 'draft',
        'starting_price' => 150,
    ]);

    Auction::factory()->create([
        'category_id' => $otherCategory->id,
        'user_id' => $seller->id,
        'title' => 'Druga kategorija',
        'status' => 'active',
        'starting_price' => 500,
    ]);

    $this->getJson("/api/categories/{$category->id}/auctions?search=Laptop&status=active&per_page=1&sort_by=starting_price&sort_direction=asc")
        ->assertOk()
        ->assertJsonPath('category.id', $category->id)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('total', 1)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('sort.by', 'starting_price')
        ->assertJsonPath('sort.direction', 'asc')
        ->assertJsonPath('auctions.0.title', 'Laptop aukcija');
});

test('it returns validation errors as json', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/categories', [
            'name' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'description',
            ],
        ]);
});

test('only admins can update and delete categories', function () {
    $category = Category::factory()->create();
    $buyer = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($buyer, 'sanctum')
        ->patchJson("/api/categories/{$category->id}", [
            'name' => 'Nova kategorija',
        ])
        ->assertForbidden();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/categories/{$category->id}", [
            'name' => 'Nova kategorija',
        ])
        ->assertOk()
        ->assertJsonPath('category.name', 'Nova kategorija');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/categories/{$category->id}")
        ->assertOk();

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});
