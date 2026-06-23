<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@auctions.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Elektronika Shop',
                'email' => 'seller.electronics@auctions.test',
                'role' => 'seller',
            ],
            [
                'name' => 'Auto Aukcije',
                'email' => 'seller.vehicles@auctions.test',
                'role' => 'seller',
            ],
            [
                'name' => 'Kolekcionar',
                'email' => 'seller.collectibles@auctions.test',
                'role' => 'seller',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                ],
            );
        }

        User::factory(10)->create();
    }
}
