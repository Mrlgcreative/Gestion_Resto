<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Server;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Catégories
        $entrees = Category::create(['name' => 'Entrées']);
        $plats = Category::create(['name' => 'Plats principaux']);
        $desserts = Category::create(['name' => 'Desserts']);
        $boissons = Category::create(['name' => 'Boissons']);

        // Produits
        $products = [
            // Entrées
            ['category_id' => $entrees->id, 'name' => 'Salade César', 'base_price' => 5.00, 'selling_price' => 8.00],
            ['category_id' => $entrees->id, 'name' => 'Soupe du jour', 'base_price' => 3.00, 'selling_price' => 5.50],
            
            // Plats principaux
            ['category_id' => $plats->id, 'name' => 'Poulet braisé', 'base_price' => 8.00, 'selling_price' => 15.00],
            ['category_id' => $plats->id, 'name' => 'Poisson grillé', 'base_price' => 10.00, 'selling_price' => 18.00],
            ['category_id' => $plats->id, 'name' => 'Steak frites', 'base_price' => 12.00, 'selling_price' => 22.00],
            ['category_id' => $plats->id, 'name' => 'Fufu & Pondu', 'base_price' => 6.00, 'selling_price' => 12.00],
            
            // Desserts
            ['category_id' => $desserts->id, 'name' => 'Crème brûlée', 'base_price' => 3.00, 'selling_price' => 6.00],
            ['category_id' => $desserts->id, 'name' => 'Glace (3 boules)', 'base_price' => 2.00, 'selling_price' => 4.50],
            
            // Boissons
            ['category_id' => $boissons->id, 'name' => 'Eau minérale', 'base_price' => 0.50, 'selling_price' => 1.50],
            ['category_id' => $boissons->id, 'name' => 'Coca-Cola', 'base_price' => 0.80, 'selling_price' => 2.00],
            ['category_id' => $boissons->id, 'name' => 'Jus naturel', 'base_price' => 1.50, 'selling_price' => 3.50],
            ['category_id' => $boissons->id, 'name' => 'Bière locale', 'base_price' => 1.00, 'selling_price' => 2.50],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Serveurs
        Server::create(['name' => 'Patrick', 'phone' => '+243 123 456 789', 'status' => 'active']);
        Server::create(['name' => 'Sandrine', 'phone' => '+243 987 654 321', 'status' => 'active']);
        Server::create(['name' => 'Emmanuel', 'phone' => '+243 555 555 555', 'status' => 'active']);
    }
}
