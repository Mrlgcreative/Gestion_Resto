<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin par défaut
        User::create([
            'role_id' => Role::where('name', 'admin')->first()->id,
            'name' => 'Administrateur',
            'email' => 'admin@resto.local',
            'phone' => '+243 000 000 000',
            'password' => 'password',
            'status' => 'active',
        ]);

        // Créer un gérant de test
        User::create([
            'role_id' => Role::where('name', 'gerant')->first()->id,
            'name' => 'Jean Gérant',
            'email' => 'gerant@resto.local',
            'phone' => '+243 111 111 111',
            'password' => 'password',
            'status' => 'active',
        ]);

        // Créer un caissier de test
        User::create([
            'role_id' => Role::where('name', 'caissier')->first()->id,
            'name' => 'Marie Caissier',
            'email' => 'caissier@resto.local',
            'phone' => '+243 222 222 222',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
