<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Créer la permission pour gérer les sessions de cuisine
        $permission = Permission::firstOrCreate(
            ['name' => 'kitchen.session'],
            [
                'slug' => 'kitchen-session',
                'group' => 'kitchen',
                'description' => 'Gérer les sessions de cuisine'
            ]
        );

        // Attribuer aux cuisiniers et admin
        $kitchenRole = Role::where('name', 'cuisinier')->first();
        $adminRole = Role::where('name', 'admin')->first();

        if ($kitchenRole) {
            $kitchenRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'kitchen.session')->delete();
    }
};
