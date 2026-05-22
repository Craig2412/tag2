<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre_usuario',
        'correo',
        'clave',
        'esta_activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'clave',
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
        'ws_channels',
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
     * Canales WebSocket a los que el usuario debe suscribirse.
     * El frontend no decide — el backend dicta según los permisos reales.
     */
    public function getWsChannelsAttribute(): array
    {
        $channels = [];

        // Canal global de atenciones: solo usuarios con permiso de alcance total
        if ($this->can('view:atenciones:todas')) {
            $channels[] = 'private-atenciones';
        }

        // Canal personal: todos los usuarios autenticados (notificaciones directas)
        $channels[] = "private-user.{$this->id}";

        return $channels;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'clave' => 'hashed',
        ];
    }

    /**
     * Reemplazar el campo password por defecto para Auth de Laravel.
     */
    public function getAuthPassword()
    {
        return $this->clave;
    }

    /**
     * Reemplazar el campo email por defecto si Laravel lo usa directamente en algunos procesos.
     * Aunque usualmente se configura en el login controller.
     */
    public function getEmailAttribute()
    {
        return $this->correo;
    }

    /**
     * Obtener el perfil de personal asociado al usuario.
     */
    public function personal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Personal::class, 'usuario_id');
    }

    /**
     * Obtener el perfil de cliente asociado al usuario.
     */
    public function cliente(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cliente::class, 'usuario_id');
    }
}
