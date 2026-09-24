<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenSession extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'total_orders_completed',
        'total_items_prepared',
        'average_preparation_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'average_preparation_time' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtient les items préparés durant cette session
     */
    public function preparedItems()
    {
        // Utiliser created_at comme début car opened_at peut avoir un décalage de timezone
        $startDate = $this->created_at;
        $endDate = $this->closed_at ?? now();
        
        // S'assurer que les dates sont dans le bon ordre
        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        return OrderItem::whereBetween('ready_at', [$startDate, $endDate])
            ->whereHas('product.category', function ($q) {
                $q->where('type', 'food');
            });
    }

    /**
     * Obtient les commandes complétées durant cette session
     */
    public function completedOrders()
    {
        // Utiliser created_at comme début car opened_at peut avoir un décalage de timezone
        $startDate = $this->created_at;
        $endDate = $this->closed_at ?? now();
        
        // S'assurer que les dates sont dans le bon ordre
        if ($endDate < $startDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        
        return Order::whereHas('items', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('ready_at', [$startDate, $endDate])
                ->whereHas('product.category', function ($q) {
                    $q->where('type', 'food');
                });
        });
    }

    /**
     * Calcule les statistiques de la session
     */
    public function calculateStats(): array
    {
        $items = $this->preparedItems()->get();
        $totalItems = $items->count();
        
        // Calculer le temps moyen de préparation
        $preparationTimes = $items->map(function ($item) {
            if ($item->ready_at && $item->created_at) {
                return $item->created_at->diffInMinutes($item->ready_at);
            }
            return null;
        })->filter();

        $avgTime = $preparationTimes->isNotEmpty() 
            ? round($preparationTimes->average(), 2) 
            : 0;

        // Compter les commandes uniques complétées
        $uniqueOrders = $items->pluck('order_id')->unique()->count();

        return [
            'total_items_prepared' => $totalItems,
            'total_orders_completed' => $uniqueOrders,
            'average_preparation_time' => $avgTime,
            'min_preparation_time' => $preparationTimes->isNotEmpty() ? $preparationTimes->min() : 0,
            'max_preparation_time' => $preparationTimes->isNotEmpty() ? $preparationTimes->max() : 0,
        ];
    }

    /**
     * Ferme la session et enregistre les statistiques
     */
    public function close(?string $notes = null): void
    {
        $stats = $this->calculateStats();

        $this->update([
            'closed_at' => now(),
            'status' => 'closed',
            'total_orders_completed' => $stats['total_orders_completed'],
            'total_items_prepared' => $stats['total_items_prepared'],
            'average_preparation_time' => $stats['average_preparation_time'],
            'notes' => $notes,
        ]);
    }

    /**
     * Obtient la session ouverte actuelle pour un utilisateur
     */
    public static function getOpenSession(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    /**
     * Ouvre une nouvelle session
     */
    public static function openSession(int $userId): self
    {
        // Fermer toute session ouverte existante
        $existingSession = self::getOpenSession($userId);
        if ($existingSession) {
            $existingSession->close('Session fermée automatiquement');
        }

        return self::create([
            'user_id' => $userId,
            'opened_at' => now(),
            'status' => 'open',
        ]);
    }
}
