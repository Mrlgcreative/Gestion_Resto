<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Service;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first()->id;
        $gerantRole = Role::where('name', 'gerant')->first()->id;
        $caissierRole = Role::where('name', 'caissier')->first()->id;

        // Admin — voit tout
        User::create([
            'role_id' => $adminRole,
            'name' => 'Administrateur',
            'email' => 'admin@resto.local',
            'phone' => '+243 000 000 000',
            'password' => 'password',
            'status' => 'active',
        ]);

        // Gérant — voit tout
        User::create([
            'role_id' => $gerantRole,
            'name' => 'Jean Gérant',
            'email' => 'gerant@resto.local',
            'phone' => '+243 111 111 111',
            'password' => 'password',
            'status' => 'active',
        ]);

        // Caissiers par service
        $services = Service::all();
        $caissierData = [
            ['service' => 'cuisine',    'name' => 'Chef Cuisinier',  'email' => 'cuisine@resto.local'],
            ['service' => 'restaurant', 'name' => 'Serveuse Resto',  'email' => 'resto@resto.local'],
            ['service' => 'bar',        'name' => 'Barman',          'email' => 'bar@resto.local'],
            ['service' => 'boite',      'name' => 'DJ Caissier',     'email' => 'boite@resto.local'],
            ['service' => 'terrasse',   'name' => 'Terrasse',        'email' => 'terrasse@resto.local'],
        ];

        foreach ($caissierData as $data) {
            $service = $services->firstWhere('slug', $data['service']);
            User::create([
                'role_id' => $caissierRole,
                'service_id' => $service->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => '+243 000 000 000',
                'password' => 'password',
                'status' => 'active',
            ]);
        }
    }
}
