<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissions()->where('slug', $slug)->exists();
    }

    public static function admin(): ?Role
    {
        return static::where('name', 'admin')->first();
    }

    public static function gerant(): ?Role
    {
        return static::where('name', 'gerant')->first();
    }

    public static function caissier(): ?Role
    {
        return static::where('name', 'caissier')->first();
    }
}
