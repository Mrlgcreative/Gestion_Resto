<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Cuisine', 'slug' => 'cuisine', 'icon' => 'fire', 'description' => 'Service de cuisine', 'sort_order' => 1],
            ['name' => 'Restaurant', 'slug' => 'restaurant', 'icon' => 'building-storefront', 'description' => 'Service de restaurant', 'sort_order' => 2],
            ['name' => 'Bar', 'slug' => 'bar', 'icon' => 'beaker', 'description' => 'Service de bar', 'sort_order' => 3],
            ['name' => 'Boite', 'slug' => 'boite', 'icon' => 'musical-note', 'description' => 'Service de boite de nuit', 'sort_order' => 4],
            ['name' => 'Terrasse', 'slug' => 'terrasse', 'icon' => 'sun', 'description' => 'Service de terrasse', 'sort_order' => 5],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
