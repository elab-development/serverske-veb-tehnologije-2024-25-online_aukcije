<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auction>
 */
class AuctionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 week', '+1 week');
        $endsAt = fake()->dateTimeBetween($startsAt, '+1 month');
        $startingPrice = fake()->randomFloat(2, 10, 10000);

        return [
            'user_id' => User::factory()->state(['role' => 'seller']),
            'category_id' => Category::factory(),
            'winner_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'starting_price' => $startingPrice,
            'current_price' => null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => fake()->randomElement(['draft', 'active', 'finished', 'cancelled']),
        ];
    }
}
