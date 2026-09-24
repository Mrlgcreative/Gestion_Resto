<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $cuisine = Service::where('slug', 'cuisine')->first();
        $resto = Service::where('slug', 'restaurant')->first();
        $bar = Service::where('slug', 'bar')->first();
        $boite = Service::where('slug', 'boite')->first();
        $terrasse = Service::where('slug', 'terrasse')->first();

        // ── Catégories ──────────────────────────────────────────
        // Cuisine
        $cuisineCat = Category::create(['name' => 'Cuisine',   'type' => 'food',  'service_id' => $cuisine->id]);
        // Restaurant
        $entrees  = Category::create(['name' => 'Entrées',    'type' => 'food',  'service_id' => $resto->id]);
        $plats    = Category::create(['name' => 'Plats',      'type' => 'food',  'service_id' => $resto->id]);
        $desserts = Category::create(['name' => 'Desserts',   'type' => 'food',  'service_id' => $resto->id]);
        // Bar
        $boissons = Category::create(['name' => 'Boissons',   'type' => 'drink', 'service_id' => $bar->id]);
        $cocktails = Category::create(['name' => 'Cocktails', 'type' => 'drink', 'service_id' => $bar->id]);
        // Boite
        $champagne = Category::create(['name' => 'Champagnes','type' => 'drink', 'service_id' => $boite->id]);
        $premium  = Category::create(['name' => 'Premium',    'type' => 'drink', 'service_id' => $boite->id]);
        // Terrasse
        $apero    = Category::create(['name' => 'Apéritifs',  'type' => 'drink', 'service_id' => $terrasse->id]);
        $grillades = Category::create(['name' => 'Grillades', 'type' => 'food',  'service_id' => $terrasse->id]);

        // ── Produits ────────────────────────────────────────────
        $products = [
            // Cuisine (plats préparés)
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Poulet braisé',     'base_price' => 8.00,  'selling_price' => 15.00],
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Poisson grillé',    'base_price' => 10.00, 'selling_price' => 18.00],
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Steak frites',      'base_price' => 12.00, 'selling_price' => 22.00],
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Fufu & Pondu',      'base_price' => 6.00,  'selling_price' => 12.00],
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Salade César',      'base_price' => 5.00,  'selling_price' => 8.00],
            ['service_id' => $cuisine->id, 'category_id' => $cuisineCat->id, 'name' => 'Crème brûlée',      'base_price' => 3.00,  'selling_price' => 6.00],

            // Restaurant (service à table)
            ['service_id' => $resto->id, 'category_id' => $entrees->id,  'name' => 'Salade César',   'base_price' => 5.00,  'selling_price' => 8.00],
            ['service_id' => $resto->id, 'category_id' => $entrees->id,  'name' => 'Soupe du jour',  'base_price' => 3.00,  'selling_price' => 5.50],
            ['service_id' => $resto->id, 'category_id' => $plats->id,    'name' => 'Poulet braisé',  'base_price' => 8.00,  'selling_price' => 15.00],
            ['service_id' => $resto->id, 'category_id' => $plats->id,    'name' => 'Poisson grillé', 'base_price' => 10.00, 'selling_price' => 18.00],
            ['service_id' => $resto->id, 'category_id' => $plats->id,    'name' => 'Steak frites',   'base_price' => 12.00, 'selling_price' => 22.00],
            ['service_id' => $resto->id, 'category_id' => $plats->id,    'name' => 'Fufu & Pondu',   'base_price' => 6.00,  'selling_price' => 12.00],
            ['service_id' => $resto->id, 'category_id' => $desserts->id, 'name' => 'Crème brûlée',   'base_price' => 3.00,  'selling_price' => 6.00],
            ['service_id' => $resto->id, 'category_id' => $desserts->id, 'name' => 'Glace (3 boules)','base_price' => 2.00,  'selling_price' => 4.50],

            // Bar (boissons, cocktails)
            ['service_id' => $bar->id, 'category_id' => $boissons->id, 'name' => 'Eau minérale',  'base_price' => 0.50, 'selling_price' => 1.50],
            ['service_id' => $bar->id, 'category_id' => $boissons->id, 'name' => 'Coca-Cola',     'base_price' => 0.80, 'selling_price' => 2.00],
            ['service_id' => $bar->id, 'category_id' => $boissons->id, 'name' => 'Jus naturel',   'base_price' => 1.50, 'selling_price' => 3.50],
            ['service_id' => $bar->id, 'category_id' => $boissons->id, 'name' => 'Bière locale',  'base_price' => 1.00, 'selling_price' => 2.50],
            ['service_id' => $bar->id, 'category_id' => $cocktails->id,'name' => 'Mojito',        'base_price' => 3.00, 'selling_price' => 7.00],
            ['service_id' => $bar->id, 'category_id' => $cocktails->id,'name' => 'Margarita',     'base_price' => 3.50, 'selling_price' => 8.00],
            ['service_id' => $bar->id, 'category_id' => $cocktails->id,'name' => 'Punch',         'base_price' => 2.50, 'selling_price' => 5.00],

            // Boite (premium)
            ['service_id' => $boite->id, 'category_id' => $champagne->id,'name' => 'Dom Pérignon',  'base_price' => 80.00, 'selling_price' => 150.00],
            ['service_id' => $boite->id, 'category_id' => $champagne->id,'name' => 'Moët & Chandon', 'base_price' => 45.00, 'selling_price' => 90.00],
            ['service_id' => $boite->id, 'category_id' => $premium->id,  'name' => 'Whisky 18 ans',  'base_price' => 25.00, 'selling_price' => 50.00],
            ['service_id' => $boite->id, 'category_id' => $premium->id,  'name' => 'Vodka Premium',  'base_price' => 20.00, 'selling_price' => 40.00],
            ['service_id' => $boite->id, 'category_id' => $premium->id,  'name' => 'Red Bull Vodka', 'base_price' => 5.00,  'selling_price' => 12.00],

            // Terrasse (apéritifs, grillades)
            ['service_id' => $terrasse->id, 'category_id' => $apero->id,    'name' => 'Kir Royal',           'base_price' => 4.00, 'selling_price' => 8.00],
            ['service_id' => $terrasse->id, 'category_id' => $apero->id,    'name' => 'Pastis',             'base_price' => 3.00, 'selling_price' => 6.00],
            ['service_id' => $terrasse->id, 'category_id' => $grillades->id,'name' => 'Brochette de bœuf',  'base_price' => 7.00, 'selling_price' => 14.00],
            ['service_id' => $terrasse->id, 'category_id' => $grillades->id,'name' => 'Merguez frites',     'base_price' => 6.00, 'selling_price' => 12.00],
            ['service_id' => $terrasse->id, 'category_id' => $grillades->id,'name' => 'Ailes de poulet',    'base_price' => 5.00, 'selling_price' => 10.00],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Serveurs (communs à tous les services)
        Server::create(['name' => 'Patrick',   'phone' => '+243 123 456 789', 'status' => 'active']);
        Server::create(['name' => 'Sandrine',  'phone' => '+243 987 654 321', 'status' => 'active']);
        Server::create(['name' => 'Emmanuel',  'phone' => '+243 555 555 555', 'status' => 'active']);
    }
}
