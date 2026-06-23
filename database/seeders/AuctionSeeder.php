<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuctionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sellers = User::query()->where('role', 'seller')->get();

        if ($sellers->isEmpty()) {
            return;
        }

        $categories = Category::query()->get()->keyBy('name');

        $realAuctions = [
            [
                'seller_email' => 'seller.electronics@auctions.test',
                'category' => 'Elektronika',
                'title' => 'Apple iPhone 15 Pro 256GB',
                'description' => 'Ocuvan telefon u originalnom pakovanju, sa kablom i zastitnom maskom.',
                'starting_price' => 650,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDays(7),
                'status' => 'draft',
            ],
            [
                'seller_email' => 'seller.electronics@auctions.test',
                'category' => 'Elektronika',
                'title' => 'PlayStation 5 konzola sa dva kontrolera',
                'description' => 'Konzola je ispravna, uz nju dolaze dva DualSense kontrolera i tri igre.',
                'starting_price' => 320,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(5),
                'status' => 'active',
            ],
            [
                'seller_email' => 'seller.vehicles@auctions.test',
                'category' => 'Vozila',
                'title' => 'Volkswagen Golf 7 1.6 TDI',
                'description' => 'Registrovan automobil sa servisnom istorijom, drugi vlasnik.',
                'starting_price' => 7400,
                'starts_at' => now()->addDays(2),
                'ends_at' => now()->addDays(12),
                'status' => 'draft',
            ],
            [
                'seller_email' => 'seller.collectibles@auctions.test',
                'category' => 'Kolekcionarstvo',
                'title' => 'Kolekcija srebrnih novcica iz Jugoslavije',
                'description' => 'Set od 18 srebrnih novcica u zastitnim kapsulama.',
                'starting_price' => 180,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(4),
                'status' => 'active',
            ],
            [
                'seller_email' => 'seller.collectibles@auctions.test',
                'category' => 'Namestaj',
                'title' => 'Restaurirana hrastova komoda',
                'description' => 'Rucno restaurirana komoda od punog drveta, pogodna za dnevnu sobu ili kancelariju.',
                'starting_price' => 240,
                'starts_at' => now()->addDays(3),
                'ends_at' => now()->addDays(10),
                'status' => 'draft',
            ],
        ];

        foreach ($realAuctions as $auction) {
            $seller = $sellers->firstWhere('email', $auction['seller_email']);
            $category = $categories->get($auction['category']);

            if (! $seller || ! $category) {
                continue;
            }

            Auction::updateOrCreate(
                ['title' => $auction['title']],
                [
                    'user_id' => $seller->id,
                    'category_id' => $category->id,
                    'winner_id' => null,
                    'description' => $auction['description'],
                    'starting_price' => $auction['starting_price'],
                    'current_price' => null,
                    'starts_at' => $auction['starts_at'],
                    'ends_at' => $auction['ends_at'],
                    'status' => $auction['status'],
                ],
            );
        }

        $availableCategories = $categories->values();

        if ($availableCategories->isEmpty()) {
            return;
        }

        foreach ($sellers as $seller) {
            Auction::factory()
                ->count(3)
                ->create([
                    'user_id' => $seller->id,
                    'category_id' => $availableCategories->random()->id,
                ]);
        }
    }
}
