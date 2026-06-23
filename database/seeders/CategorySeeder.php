<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronika',
                'description' => 'Telefoni, racunari, konzole, audio oprema i druga potrosacka elektronika.',
            ],
            [
                'name' => 'Vozila',
                'description' => 'Automobili, motocikli, bicikli, rezervni delovi i dodatna oprema za vozila.',
            ],
            [
                'name' => 'Nekretnine',
                'description' => 'Stanovi, kuce, placevi, poslovni prostori i druge nepokretnosti na aukciji.',
            ],
            [
                'name' => 'Kolekcionarstvo',
                'description' => 'Numizmatika, filatelija, antikviteti, umetnine i drugi kolekcionarski predmeti.',
            ],
            [
                'name' => 'Namestaj',
                'description' => 'Kucni i kancelarijski namestaj, dekoracija i oprema za enterijer.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']],
            );
        }

        Category::factory()->count(10)->create();
    }
}
