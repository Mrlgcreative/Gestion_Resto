<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Role;
use App\Models\Permission;

echo "=== Configuration des permissions cuisine ===\n\n";

// 1. Vérifier/Créer les permissions cuisine
$kitchenPermissions = [
    ['name' => 'Voir la cuisine', 'slug' => 'kitchen.view'],
    ['name' => 'Modifier statut cuisine', 'slug' => 'kitchen.update'],
];

foreach ($kitchenPermissions as $perm) {
    $existing = Permission::where('slug', $perm['slug'])->first();
    if (!$existing) {
        Permission::create($perm);
        echo "✅ Permission créée: {$perm['slug']}\n";
    } else {
        echo "ℹ️ Permission existe: {$perm['slug']}\n";
    }
}

// 2. Trouver ou créer le rôle cuisinier (utilise 'name' pas 'slug')
$cuisinierRole = Role::where('name', 'cuisinier')->first();
if (!$cuisinierRole) {
    $cuisinierRole = Role::create([
        'name' => 'cuisinier',
        'description' => 'Cuisinier - Accès cuisine',
    ]);
    echo "\n✅ Rôle Cuisinier créé\n";
} else {
    echo "\nℹ️ Rôle Cuisinier existe déjà\n";
}

// 3. Assigner les permissions au rôle cuisinier
$permissionIds = Permission::whereIn('slug', ['kitchen.view', 'kitchen.update'])->pluck('id')->toArray();
$cuisinierRole->permissions()->syncWithoutDetaching($permissionIds);
echo "✅ Permissions assignées au rôle Cuisinier\n";

// 4. Vérifier aussi le rôle admin
$adminRole = Role::where('name', 'admin')->first();
if ($adminRole) {
    $adminRole->permissions()->syncWithoutDetaching($permissionIds);
    echo "✅ Permissions assignées au rôle Admin\n";
}

// 5. Vérifier aussi le rôle gérant
$gerantRole = Role::where('name', 'gerant')->first();
if ($gerantRole) {
    $gerantRole->permissions()->syncWithoutDetaching($permissionIds);
    echo "✅ Permissions assignées au rôle Gérant\n";
}

echo "\n=== Terminé ===\n";

// Afficher les permissions du cuisinier
echo "\nPermissions du cuisinier:\n";
$cuisinierRole->load('permissions');
foreach ($cuisinierRole->permissions as $p) {
    echo "  - {$p->slug}\n";
}
