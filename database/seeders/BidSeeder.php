<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Seeder;

class BidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::query()
            ->where('role', 'buyer')
            ->take(5)
            ->get();

        if ($buyers->isEmpty()) {
            return;
        }

        $auctions = Auction::query()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->take(5)
            ->get();

        foreach ($auctions as $auction) {
            $highestAmount = (float) $auction->starting_price;
            $winnerId = null;

            foreach ($buyers->take(3) as $index => $buyer) {
                $amount = $highestAmount + (($index + 1) * 25);

                Bid::updateOrCreate(
                    [
                        'user_id' => $buyer->id,
                        'auction_id' => $auction->id,
                    ],
                    [
                        'amount' => $amount,
                    ],
                );

                $highestAmount = $amount;
                $winnerId = $buyer->id;
            }

            $auction->update([
                'current_price' => $highestAmount,
                'winner_id' => $winnerId,
            ]);
        }
    }
}
