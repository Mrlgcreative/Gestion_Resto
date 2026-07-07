<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'service_id',
        'name',
        'phone',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Relations
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function cashierSessions(): HasMany
    {
        return $this->hasMany(CashierSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Status helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isGerant(): bool
    {
        return $this->role?->name === 'gerant';
    }

    public function isCaissier(): bool
    {
        return $this->role?->name === 'caissier';
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasPermission(string $slug): bool
    {
        // Admin a toutes les permissions
        if ($this->isAdmin()) {
            return true;
        }

        return $this->role?->hasPermission($slug) ?? false;
    }

    public function can($ability, $arguments = []): bool
    {
        // Vérifier d'abord les permissions personnalisées
        if ($this->hasPermission($ability)) {
            return true;
        }

        return parent::can($ability, $arguments);
    }

    // Session helpers
    public function openSession(): ?CashierSession
    {
        return $this->cashierSessions()->where('status', 'open')->first();
    }

    public function hasOpenSession(): bool
    {
        return $this->openSession() !== null;
    }
}
