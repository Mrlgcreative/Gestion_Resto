# Système de Sessions de Cuisine 🔥

## Fonctionnalités ajoutées

### 1. **Gestion des Sessions**

Les cuisiniers peuvent maintenant :

-   ✅ Ouvrir une session avant de commencer leur service
-   ✅ Fermer leur session avec des notes optionnelles
-   ✅ Voir les statistiques en temps réel de leur session active

### 2. **Tracking Automatique**

Le système enregistre automatiquement :

-   Nombre total de commandes complétées
-   Nombre total de plats préparés
-   Temps moyen de préparation
-   Temps min/max de préparation
-   Durée totale de la session

### 3. **Rapports PDF**

Pour chaque session fermée, possibilité de générer un rapport PDF contenant :

-   Informations de la session (cuisinier, dates, durée)
-   Statistiques complètes
-   Détail des plats préparés par catégorie
-   Tableau avec temps de préparation pour chaque plat
-   Notes de session

## Fichiers créés

### Base de données

-   `database/migrations/2026_01_07_000001_create_kitchen_sessions_table.php`
-   `database/migrations/2026_01_07_000002_add_kitchen_session_permission.php`

### Modèles

-   `app/Models/KitchenSession.php` - Modèle avec méthodes utiles

### Contrôleurs

-   `app/Http/Controllers/KitchenSessionController.php` - Gestion complète des sessions

### Vues

-   `resources/views/reports/kitchen-session.blade.php` - Template PDF
-   `resources/js/Components/KitchenSessionControl.vue` - Widget de contrôle de session
-   `resources/js/Pages/Kitchen/Sessions/Index.vue` - Liste des sessions

## Routes ajoutées

```php
// Kitchen Sessions
Route::get('/kitchen/sessions', [KitchenSessionController::class, 'index'])->name('kitchen.sessions.index');
Route::post('/kitchen/sessions/open', [KitchenSessionController::class, 'open'])->name('kitchen.sessions.open');
Route::post('/kitchen/sessions/close', [KitchenSessionController::class, 'close'])->name('kitchen.sessions.close');
Route::get('/kitchen/sessions/current', [KitchenSessionController::class, 'current'])->name('kitchen.sessions.current');
Route::get('/kitchen/sessions/{session}', [KitchenSessionController::class, 'show'])->name('kitchen.sessions.show');
Route::get('/kitchen/sessions/{session}/report', [KitchenSessionController::class, 'report'])->name('kitchen.sessions.report');
```

## Utilisation

### Pour le cuisinier :

1. **Ouvrir une session**

    - Aller sur la page Cuisine
    - Cliquer sur "🔥 Ouvrir une Session"

2. **Pendant la session**

    - Le widget affiche en temps réel :
        - Durée de la session
        - Nombre de commandes complétées
        - Nombre de plats préparés
        - Temps moyen de préparation

3. **Fermer la session**

    - Cliquer sur "Fermer la Session"
    - Optionnellement ajouter des notes
    - Confirmer

4. **Consulter l'historique**
    - Accéder via le menu ou `/kitchen/sessions`
    - Voir toutes les sessions passées
    - Télécharger le rapport PDF

### Pour l'admin :

-   Voir toutes les sessions de tous les cuisiniers
-   Analyser les performances
-   Exporter les rapports

## Permissions

La permission `kitchen.session` a été créée et attribuée à :

-   Rôle `cuisinier`
-   Rôle `admin`

## Package installé

-   `barryvdh/laravel-dompdf` (v3.1.1) - Pour la génération de PDF

## Améliorations futures possibles

1. Graphiques de performance par cuisinier
2. Comparaison entre sessions
3. Alertes si temps de préparation anormalement long
4. Export Excel des statistiques
5. Dashboard analytics pour la cuisine
6. Intégration avec le système de paie

---

**Installation terminée avec succès ! ✅**

Les cuisiniers peuvent maintenant gérer leurs sessions et consulter leurs rapports de performance.
