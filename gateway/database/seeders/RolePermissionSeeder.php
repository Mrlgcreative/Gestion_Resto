<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les rôles
        $admin = Role::create([
            'name' => 'admin',
            'description' => 'Administrateur avec accès complet',
        ]);

        $gerant = Role::create([
            'name' => 'gerant',
            'description' => 'Gérant avec accès opérationnel',
        ]);

        $caissier = Role::create([
            'name' => 'caissier',
            'description' => 'Caissier avec accès limité',
        ]);

        // Définir toutes les permissions
        $permissions = [
            // Système
            ['name' => 'Accès Dashboard', 'slug' => 'dashboard.view', 'group' => 'system'],
            ['name' => 'Configuration système', 'slug' => 'settings.manage', 'group' => 'system'],

            // Utilisateurs
            ['name' => 'Voir les utilisateurs', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Créer un utilisateur', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Modifier un utilisateur', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Supprimer un utilisateur', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'Gérer les rôles', 'slug' => 'roles.manage', 'group' => 'users'],

            // Serveurs
            ['name' => 'Voir les serveurs', 'slug' => 'servers.view', 'group' => 'servers'],
            ['name' => 'Créer un serveur', 'slug' => 'servers.create', 'group' => 'servers'],
            ['name' => 'Modifier un serveur', 'slug' => 'servers.edit', 'group' => 'servers'],
            ['name' => 'Supprimer un serveur', 'slug' => 'servers.delete', 'group' => 'servers'],

            // Catégories
            ['name' => 'Voir les catégories', 'slug' => 'categories.view', 'group' => 'products'],
            ['name' => 'Créer une catégorie', 'slug' => 'categories.create', 'group' => 'products'],
            ['name' => 'Modifier une catégorie', 'slug' => 'categories.edit', 'group' => 'products'],
            ['name' => 'Supprimer une catégorie', 'slug' => 'categories.delete', 'group' => 'products'],

            // Produits
            ['name' => 'Voir les produits', 'slug' => 'products.view', 'group' => 'products'],
            ['name' => 'Créer un produit', 'slug' => 'products.create', 'group' => 'products'],
            ['name' => 'Modifier un produit', 'slug' => 'products.edit', 'group' => 'products'],
            ['name' => 'Supprimer un produit', 'slug' => 'products.delete', 'group' => 'products'],

            // Ingrédients / Stocks
            ['name' => 'Voir les stocks', 'slug' => 'stocks.view', 'group' => 'stocks'],
            ['name' => 'Gérer les stocks', 'slug' => 'stocks.manage', 'group' => 'stocks'],
            ['name' => 'Entrée de stock', 'slug' => 'stocks.in', 'group' => 'stocks'],
            ['name' => 'Sortie de stock', 'slug' => 'stocks.out', 'group' => 'stocks'],

            // Devises
            ['name' => 'Voir les devises', 'slug' => 'currencies.view', 'group' => 'currencies'],
            ['name' => 'Gérer les devises', 'slug' => 'currencies.manage', 'group' => 'currencies'],
            ['name' => 'Gérer les taux de change', 'slug' => 'exchange_rates.manage', 'group' => 'currencies'],

            // Sessions caissier
            ['name' => 'Voir toutes les sessions', 'slug' => 'sessions.view_all', 'group' => 'sessions'],
            ['name' => 'Voir ses sessions', 'slug' => 'sessions.view_own', 'group' => 'sessions'],
            ['name' => 'Ouvrir une session', 'slug' => 'sessions.open', 'group' => 'sessions'],
            ['name' => 'Fermer une session', 'slug' => 'sessions.close', 'group' => 'sessions'],
            ['name' => 'Forcer fermeture session', 'slug' => 'sessions.force_close', 'group' => 'sessions'],

            // Commandes
            ['name' => 'Voir toutes les commandes', 'slug' => 'orders.view_all', 'group' => 'orders'],
            ['name' => 'Voir ses commandes', 'slug' => 'orders.view_own', 'group' => 'orders'],
            ['name' => 'Créer une commande', 'slug' => 'orders.create', 'group' => 'orders'],
            ['name' => 'Modifier une commande', 'slug' => 'orders.edit', 'group' => 'orders'],
            ['name' => 'Annuler une commande', 'slug' => 'orders.cancel', 'group' => 'orders'],

            // Paiements
            ['name' => 'Voir tous les paiements', 'slug' => 'payments.view_all', 'group' => 'payments'],
            ['name' => 'Voir ses paiements', 'slug' => 'payments.view_own', 'group' => 'payments'],
            ['name' => 'Encaisser', 'slug' => 'payments.create', 'group' => 'payments'],

            // Rapports
            ['name' => 'Voir tous les rapports', 'slug' => 'reports.view_all', 'group' => 'reports'],
            ['name' => 'Voir son palmarès', 'slug' => 'reports.view_own', 'group' => 'reports'],
            ['name' => 'Exporter les rapports', 'slug' => 'reports.export', 'group' => 'reports'],

            // Journal d'activité
            ['name' => 'Voir le journal', 'slug' => 'activity_logs.view', 'group' => 'activity'],
        ];

        // Créer les permissions
        foreach ($permissions as $permData) {
            Permission::create($permData);
        }

        // Attribuer les permissions au Gérant
        $gerantPermissions = [
            'dashboard.view',
            'servers.view', 'servers.create', 'servers.edit', 'servers.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'stocks.view', 'stocks.manage', 'stocks.in', 'stocks.out',
            'sessions.view_all',
            'orders.view_all',
            'payments.view_all',
            'reports.view_all', 'reports.export',
        ];

        $gerantPerms = Permission::whereIn('slug', $gerantPermissions)->get();
        $gerant->permissions()->attach($gerantPerms);

        // Attribuer les permissions au Caissier
        $caissierPermissions = [
            'dashboard.view',
            'products.view',
            'servers.view',
            'sessions.view_own', 'sessions.open', 'sessions.close',
            'orders.view_own', 'orders.create',
            'payments.view_own', 'payments.create',
            'reports.view_own',
        ];

        $caissierPerms = Permission::whereIn('slug', $caissierPermissions)->get();
        $caissier->permissions()->attach($caissierPerms);

        // L'admin a automatiquement toutes les permissions (géré dans le modèle User)
    }
}
