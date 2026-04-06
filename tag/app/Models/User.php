<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que se deben agregar automáticamente al JSON.
     *
     * @var list<string>
     */
    protected $appends = [
        'all_permissions',
        'role_names',
    ];

    /**
     * Obtener todos los nombres de los permisos del usuario (heredados y directos).
     */
    public function getAllPermissionsAttribute(): \Illuminate\Support\Collection
    {
        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * Obtener los nombres de los roles del usuario.
     */
    public function getRoleNamesAttribute(): \Illuminate\Support\Collection
    {
        return $this->getRoleNames();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
