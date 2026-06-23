<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it finishes ended active auctions and sets the winner from the highest bid', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $buyer = User::factory()->create(['role' => 'buyer']);
    $highestBuyer = User::factory()->create(['role' => 'buyer']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => 150,
        'winner_id' => $buyer->id,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subMinute(),
    ]);

    Bid::factory()->create([
        'user_id' => $buyer->id,
        'auction_id' => $auction->id,
        'amount' => 150,
    ]);
    Bid::factory()->create([
        'user_id' => $highestBuyer->id,
        'auction_id' => $auction->id,
        'amount' => 220,
    ]);

    $this->artisan('auctions:finish-ended')
        ->expectsOutput('Finished 1 auctions.')
        ->assertExitCode(0);

    $auction->refresh();

    expect($auction->status)->toBe('finished')
        ->and($auction->current_price)->toBe('220.00')
        ->and($auction->winner_id)->toBe($highestBuyer->id);
});

test('it finishes ended active auctions without bids', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starting_price' => 100,
        'current_price' => null,
        'winner_id' => null,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('auctions:finish-ended')
        ->expectsOutput('Finished 1 auctions.')
        ->assertExitCode(0);

    $auction->refresh();

    expect($auction->status)->toBe('finished')
        ->and($auction->current_price)->toBeNull()
        ->and($auction->winner_id)->toBeNull();
});

test('it does not finish active auctions that have not ended', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $auction = Auction::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $this->artisan('auctions:finish-ended')
        ->expectsOutput('Finished 0 auctions.')
        ->assertExitCode(0);

    expect($auction->refresh()->status)->toBe('active');
});
