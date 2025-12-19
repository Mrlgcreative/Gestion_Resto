<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'target',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected $appends = ['action_label', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Traductions des actions en français
    public function getActionLabelAttribute(): string
    {
        $actions = [
            // Authentification
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            
            // CRUD générique
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            
            // Commandes
            'order_created' => 'Nouvelle commande',
            'order_paid' => 'Paiement commande',
            'order_canceled' => 'Annulation commande',
            
            // Sessions de caisse
            'session_opened' => 'Ouverture de caisse',
            'session_closed' => 'Fermeture de caisse',
            
            // Stock
            'stock_in' => 'Entrée de stock',
            'stock_out' => 'Sortie de stock',
            'stock_adjusted' => 'Ajustement de stock',
            
            // Paramètres
            'settings_updated' => 'Paramètres modifiés',
            'order_options_updated' => 'Options de commande modifiées',
            'exchange_options_updated' => 'Options de change modifiées',
            
            // Utilisateurs
            'user_created' => 'Utilisateur créé',
            'user_updated' => 'Utilisateur modifié',
            'user_deleted' => 'Utilisateur supprimé',
            
            // Devises
            'currency_created' => 'Devise ajoutée',
            'currency_updated' => 'Devise modifiée',
            'currency_deleted' => 'Devise supprimée',
            'default_currency_changed' => 'Devise par défaut modifiée',
            
            // Produits
            'product_created' => 'Produit créé',
            'product_updated' => 'Produit modifié',
            'product_deleted' => 'Produit supprimé',
            
            // Catégories
            'category_created' => 'Catégorie créée',
            'category_updated' => 'Catégorie modifiée',
            'category_deleted' => 'Catégorie supprimée',
            
            // Serveurs
            'server_created' => 'Serveur ajouté',
            'server_updated' => 'Serveur modifié',
            'server_deleted' => 'Serveur supprimé',
        ];

        return $actions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    // Description détaillée de l'action
    public function getDescriptionAttribute(): string
    {
        $target = $this->target;
        $props = $this->properties ?? [];
        $id = $props['id'] ?? null;

        // Traduction des modèles
        $models = [
            'Order' => 'commande',
            'Product' => 'produit',
            'Category' => 'catégorie',
            'User' => 'utilisateur',
            'Server' => 'serveur',
            'Ingredient' => 'ingrédient',
            'Currency' => 'devise',
            'CashierSession' => 'session de caisse',
            'Setting' => 'paramètres',
            'Payment' => 'paiement',
            'ExchangeRate' => 'taux de change',
        ];

        $targetLabel = $models[$target] ?? strtolower($target ?? '');

        switch ($this->action) {
            case 'login':
                return "L'utilisateur s'est connecté à l'application.";
            
            case 'logout':
                return "L'utilisateur s'est déconnecté de l'application.";
            
            case 'created':
                return $this->buildCreationDescription($targetLabel, $id, $props);
            
            case 'updated':
                return $this->buildUpdateDescription($targetLabel, $id, $props);
            
            case 'deleted':
                return "Le/la {$targetLabel}" . ($id ? " #{$id}" : "") . " a été supprimé(e) du système.";
            
            case 'order_created':
                $total = isset($props['total']) ? number_format($props['total'], 2) . ' $' : '';
                return "Une nouvelle commande" . ($id ? " #{$id}" : "") . " a été créée" . ($total ? " pour un montant de {$total}" : "") . ".";
            
            case 'order_paid':
                $amount = isset($props['amount']) ? number_format($props['amount'], 2) . ' $' : '';
                $method = $this->getPaymentMethodLabel($props['method'] ?? '');
                return "La commande" . ($id ? " #{$id}" : "") . " a été payée" . ($amount ? " ({$amount})" : "") . ($method ? " par {$method}" : "") . ".";
            
            case 'order_canceled':
                $reason = $props['reason'] ?? '';
                return "La commande" . ($id ? " #{$id}" : "") . " a été annulée" . ($reason ? ". Raison: {$reason}" : ".") ;
            
            case 'session_opened':
                $amount = isset($props['opening_amount']) ? number_format($props['opening_amount'], 2) . ' $' : '';
                return "Une session de caisse a été ouverte" . ($amount ? " avec un fond de caisse de {$amount}" : "") . ".";
            
            case 'session_closed':
                $expected = isset($props['expected']) ? number_format($props['expected'], 2) . ' $' : '';
                $actual = isset($props['actual']) ? number_format($props['actual'], 2) . ' $' : '';
                $diff = isset($props['difference']) ? number_format($props['difference'], 2) . ' $' : '';
                $desc = "La session de caisse a été clôturée.";
                if ($expected && $actual) {
                    $desc .= " Attendu: {$expected}, Réel: {$actual}";
                    if ($diff) {
                        $desc .= ", Écart: {$diff}";
                    }
                }
                return $desc;
            
            case 'stock_in':
                $qty = $props['quantity'] ?? '';
                $ingredient = $props['ingredient'] ?? '';
                $reason = $props['reason'] ?? '';
                return "Entrée de stock" . ($ingredient ? " pour {$ingredient}" : "") . ($qty ? ": +{$qty}" : "") . ($reason ? ". Raison: {$reason}" : ".") ;
            
            case 'stock_out':
                $qty = $props['quantity'] ?? '';
                $ingredient = $props['ingredient'] ?? '';
                $reason = $props['reason'] ?? '';
                return "Sortie de stock" . ($ingredient ? " pour {$ingredient}" : "") . ($qty ? ": -{$qty}" : "") . ($reason ? ". Raison: {$reason}" : ".") ;
            
            case 'settings_updated':
                $changes = $props['changes'] ?? [];
                if (!empty($changes)) {
                    $fields = implode(', ', array_map(fn($f) => $this->translateField($f), $changes));
                    return "Les paramètres suivants ont été modifiés: {$fields}.";
                }
                return "Les paramètres du restaurant ont été modifiés.";
            
            case 'order_options_updated':
                return "Les options de commande (TVA, service, activation) ont été modifiées.";
            
            case 'exchange_options_updated':
                return "Les options de taux de change ont été modifiées.";
            
            case 'default_currency_changed':
                $currency = $props['currency'] ?? '';
                return "La devise par défaut a été changée" . ($currency ? " en {$currency}" : "") . ".";
            
            case 'product_created':
            case 'product_updated':
            case 'product_deleted':
                $name = $props['name'] ?? '';
                $action = str_contains($this->action, 'created') ? 'créé' : (str_contains($this->action, 'updated') ? 'modifié' : 'supprimé');
                return "Le produit" . ($name ? " \"{$name}\"" : ($id ? " #{$id}" : "")) . " a été {$action}.";
            
            case 'user_created':
            case 'user_updated':
            case 'user_deleted':
                $name = $props['name'] ?? '';
                $action = str_contains($this->action, 'created') ? 'créé' : (str_contains($this->action, 'updated') ? 'modifié' : 'supprimé');
                return "L'utilisateur" . ($name ? " \"{$name}\"" : ($id ? " #{$id}" : "")) . " a été {$action}.";
            
            default:
                if ($target && $id) {
                    return "Action sur {$targetLabel} #{$id}.";
                }
                return "Action effectuée sur le système.";
        }
    }

    private function buildCreationDescription(string $targetLabel, $id, array $props): string
    {
        $name = $props['name'] ?? null;
        $desc = "Un(e) nouveau/nouvelle {$targetLabel}";
        
        if ($name) {
            $desc .= " \"{$name}\"";
        } elseif ($id) {
            $desc .= " #{$id}";
        }
        
        return $desc . " a été créé(e) dans le système.";
    }

    private function buildUpdateDescription(string $targetLabel, $id, array $props): string
    {
        $changes = $props['changes'] ?? [];
        $name = $props['name'] ?? null;
        
        $desc = "Le/la {$targetLabel}";
        if ($name) {
            $desc .= " \"{$name}\"";
        } elseif ($id) {
            $desc .= " #{$id}";
        }
        
        $desc .= " a été modifié(e)";
        
        if (!empty($changes)) {
            $fields = array_map(fn($f) => $this->translateField($f), array_keys($changes));
            $desc .= ". Champs modifiés: " . implode(', ', $fields);
        }
        
        return $desc . ".";
    }

    private function getPaymentMethodLabel(string $method): string
    {
        return match($method) {
            'cash' => 'espèces',
            'card' => 'carte bancaire',
            'mobile' => 'paiement mobile',
            default => $method,
        };
    }

    private function translateField(string $field): string
    {
        $fields = [
            'name' => 'nom',
            'email' => 'email',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'price' => 'prix',
            'quantity' => 'quantité',
            'status' => 'statut',
            'description' => 'description',
            'image' => 'image',
            'logo' => 'logo',
            'restaurant_name' => 'nom du restaurant',
            'tax_rate' => 'taux de TVA',
            'service_charge' => 'frais de service',
            'orders_enabled' => 'commandes activées',
            'exchange_rate_enabled' => 'taux de change activé',
            'category_id' => 'catégorie',
            'role_id' => 'rôle',
            'password' => 'mot de passe',
        ];

        return $fields[$field] ?? str_replace('_', ' ', $field);
    }

    // Enregistrer une activité
    public static function log(string $action, ?string $target = null, ?array $properties = null, ?int $userId = null): ActivityLog
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'target' => $target,
            'properties' => $properties,
        ]);
    }

    // Helpers pour les actions courantes
    public static function logCreation(string $model, int $id, array $extra = []): ActivityLog
    {
        return static::log('created', $model, array_merge(['id' => $id], $extra));
    }

    public static function logUpdate(string $model, int $id, array $changes = [], array $extra = []): ActivityLog
    {
        return static::log('updated', $model, array_merge(['id' => $id, 'changes' => $changes], $extra));
    }

    public static function logDeletion(string $model, int $id, array $extra = []): ActivityLog
    {
        return static::log('deleted', $model, array_merge(['id' => $id], $extra));
    }

    public static function logLogin(): ActivityLog
    {
        return static::log('login');
    }

    public static function logLogout(): ActivityLog
    {
        return static::log('logout');
    }
}
