<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bid>
 */
class BidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startingPrice = fake()->randomFloat(2, 10, 10000);
        $amount = $startingPrice + fake()->randomFloat(2, 10, 1000);

        return [
            'user_id' => User::factory()->state(['role' => 'buyer']),
            'auction_id' => Auction::factory()->state([
                'status' => 'active',
                'starting_price' => $startingPrice,
                'current_price' => $amount,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeek(),
            ]),
            'amount' => $amount,
        ];
    }
}
