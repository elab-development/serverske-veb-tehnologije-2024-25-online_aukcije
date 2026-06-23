<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('buyers can create a bid on an active auction', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $category = Category::factory()->create();
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => null,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $auction->id,
            'amount' => 150,
        ])
        ->assertCreated()
        ->assertJsonPath('bid.amount', '150.00')
        ->assertJsonPath('auction.current_price', '150.00')
        ->assertJsonPath('auction.winner_id', $buyer->id);

    $this->assertDatabaseHas('bids', [
        'user_id' => $buyer->id,
        'auction_id' => $auction->id,
        'amount' => 150,
    ]);
});

test('buyers update their existing bid when they bid again', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => null,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $auction->id,
            'amount' => 150,
        ])
        ->assertCreated();

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $auction->id,
            'amount' => 220,
        ])
        ->assertOk()
        ->assertJsonPath('bid.amount', '220.00')
        ->assertJsonPath('auction.current_price', '220.00');

    $this->assertDatabaseCount('bids', 1);
});

test('bid amount must beat the current or starting price', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => null,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $auction->id,
            'amount' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('only buyers can bid and only while auction accepts bids', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $draftAuction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'draft',
        'starting_price' => 100,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);
    $futureAuction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
    ]);

    $this->actingAs($seller, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $draftAuction->id,
            'amount' => 150,
        ])
        ->assertForbidden();

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $draftAuction->id,
            'amount' => 150,
        ])
        ->assertStatus(409);

    $this->actingAs($buyer, 'sanctum')
        ->postJson('/api/bids', [
            'auction_id' => $futureAuction->id,
            'amount' => 150,
        ])
        ->assertStatus(409);
});

test('bid listing is scoped by role', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $otherBuyer = User::factory()->create(['role' => 'buyer']);
    $admin = User::factory()->create(['role' => 'admin']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => 180,
        'winner_id' => $otherBuyer->id,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ]);

    Bid::factory()->create([
        'user_id' => $buyer->id,
        'auction_id' => $auction->id,
        'amount' => 140,
    ]);
    Bid::factory()->create([
        'user_id' => $otherBuyer->id,
        'auction_id' => $auction->id,
        'amount' => 180,
    ]);

    $this->actingAs($buyer, 'sanctum')
        ->getJson("/api/auctions/{$auction->id}/bids")
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('bid.user_id', $buyer->id);

    $this->actingAs($seller, 'sanctum')
        ->getJson("/api/auctions/{$auction->id}/bids")
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonCount(2, 'bids');

    $this->actingAs($otherSeller, 'sanctum')
        ->getJson("/api/auctions/{$auction->id}/bids")
        ->assertForbidden();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/auctions/{$auction->id}/bids")
        ->assertOk()
        ->assertJsonPath('count', 2);
});
