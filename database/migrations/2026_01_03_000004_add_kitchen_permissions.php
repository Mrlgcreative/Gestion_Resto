<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Créer les permissions cuisine
        $kitchenPermissions = [
            ['name' => 'Voir la cuisine', 'slug' => 'kitchen.view', 'group' => 'kitchen'],
            ['name' => 'Modifier statut cuisine', 'slug' => 'kitchen.update', 'group' => 'kitchen'],
        ];

        foreach ($kitchenPermissions as $permData) {
            Permission::firstOrCreate(['slug' => $permData['slug']], $permData);
        }

        // Créer le rôle cuisinier
        $cuisinier = Role::firstOrCreate(
            ['name' => 'cuisinier'],
            ['description' => 'Cuisinier avec accès à l\'écran cuisine']
        );

        // Attribuer les permissions au cuisinier
        $perms = Permission::whereIn('slug', ['kitchen.view', 'kitchen.update', 'dashboard.view'])->get();
        $cuisinier->permissions()->syncWithoutDetaching($perms);

        // Ajouter les permissions cuisine au gérant et admin
        $gerant = Role::where('name', 'gerant')->first();
        if ($gerant) {
            $gerant->permissions()->syncWithoutDetaching($perms);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['kitchen.view', 'kitchen.update'])->delete();
        Role::where('name', 'cuisinier')->delete();
    }
};
