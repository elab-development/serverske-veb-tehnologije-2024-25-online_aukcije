<?php

use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it lists all auctions publicly', function () {
    Auction::factory()->count(2)->create();

    $response = $this->getJson('/api/auctions');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'auctions')
        ->assertJsonPath('total', 2)
        ->assertJsonPath('count', 2);
});

test('it searches filters paginates and sorts auctions', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $category = Category::factory()->create(['name' => 'Test elektronika']);

    Auction::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'title' => 'PlayStation 5 konzola',
        'description' => 'Konzola sa dva kontrolera.',
        'starting_price' => 300,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(5),
    ]);

    Auction::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'title' => 'Stari laptop',
        'description' => 'Laptop za delove.',
        'starting_price' => 50,
        'status' => 'draft',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(8),
    ]);

    $response = $this->getJson('/api/auctions?search=PlayStation&status=active&category_id='.$category->id.'&seller_id='.$seller->id.'&per_page=1&sort_by=starting_price&sort_direction=asc');

    $response
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('total', 1)
        ->assertJsonPath('per_page', 1)
        ->assertJsonPath('sort.by', 'starting_price')
        ->assertJsonPath('sort.direction', 'asc')
        ->assertJsonPath('auctions.0.title', 'PlayStation 5 konzola');
});

test('it exports auctions as a csv file', function () {
    $seller = User::factory()->create([
        'name' => 'Test Seller',
        'email' => 'seller@example.test',
        'role' => 'seller',
    ]);
    $category = Category::factory()->create(['name' => 'Elektronika']);

    Auction::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'title' => 'PlayStation 5 konzola',
        'description' => 'Konzola sa dva kontrolera.',
        'starting_price' => 300,
        'current_price' => null,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(5),
    ]);

    $response = $this->get('/api/auctions/export');

    $response
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain('id,title,description,category,seller,seller_email,winner,winner_email,starting_price,current_price,status,starts_at,ends_at,bids_count,created_at,updated_at')
        ->toContain('"PlayStation 5 konzola"')
        ->toContain('Elektronika')
        ->toContain('seller@example.test');
});

test('only sellers can create auctions', function () {
    $category = Category::factory()->create();
    $buyer = User::factory()->create(['role' => 'buyer']);

    $payload = [
        'category_id' => $category->id,
        'title' => 'Test aukcija',
        'description' => 'Opis test aukcije.',
        'starting_price' => 100,
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDays(7)->toDateTimeString(),
    ];

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/auctions', $payload)
        ->assertForbidden();

    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller, 'sanctum')
        ->postJson('/api/auctions', $payload)
        ->assertCreated()
        ->assertJsonPath('auction.title', 'Test aukcija')
        ->assertJsonPath('auction.user_id', $seller->id)
        ->assertJsonPath('auction.current_price', null);
});

test('current price cannot be set manually', function () {
    $category = Category::factory()->create();
    $seller = User::factory()->create(['role' => 'seller']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'status' => 'draft',
    ]);

    $this->actingAs($seller, 'sanctum')
        ->postJson('/api/auctions', [
            'category_id' => $category->id,
            'title' => 'Nova aukcija',
            'description' => 'Opis nove aukcije.',
            'starting_price' => 100,
            'current_price' => 120,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(7)->toDateTimeString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_price']);

    $this->actingAs($seller, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'current_price' => 120,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_price']);
});

test('only auction owner or admin can update auctions', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    $auction = Auction::factory()->create([
        'user_id' => $owner->id,
        'status' => 'draft',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(7),
    ]);

    $this->actingAs($otherSeller, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'title' => 'Tudja izmena',
        ])
        ->assertForbidden();

    $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'title' => 'Izmenjena aukcija',
        ])
        ->assertOk()
        ->assertJsonPath('auction.title', 'Izmenjena aukcija');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'status' => 'cancelled',
        ])
        ->assertOk()
        ->assertJsonPath('auction.status', 'cancelled');
});

test('active auctions can only be partially updated', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $auction = Auction::factory()->create([
        'user_id' => $owner->id,
        'status' => 'active',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(5),
    ]);

    $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'title' => 'Novi naslov',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/auctions/{$auction->id}", [
            'description' => 'Azuriran opis aktivne aukcije.',
            'status' => 'cancelled',
        ])
        ->assertOk()
        ->assertJsonPath('auction.description', 'Azuriran opis aktivne aukcije.')
        ->assertJsonPath('auction.status', 'cancelled');
});

test('only auction owner or admin can delete auctions', function () {
    $owner = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $auction = Auction::factory()->create([
        'user_id' => $owner->id,
        'status' => 'draft',
    ]);

    $this->actingAs($buyer, 'sanctum')
        ->deleteJson("/api/auctions/{$auction->id}")
        ->assertForbidden();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/auctions/{$auction->id}")
        ->assertOk();

    $this->assertDatabaseMissing('auctions', [
        'id' => $auction->id,
    ]);
});

test('active and finished auctions cannot be deleted', function () {
    $owner = User::factory()->create(['role' => 'seller']);

    $activeAuction = Auction::factory()->create([
        'user_id' => $owner->id,
        'status' => 'active',
    ]);

    $finishedAuction = Auction::factory()->create([
        'user_id' => $owner->id,
        'status' => 'finished',
    ]);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/auctions/{$activeAuction->id}")
        ->assertStatus(409);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/auctions/{$finishedAuction->id}")
        ->assertStatus(409);
});
