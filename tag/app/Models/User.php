<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'cedula',
        'telefono',
        'porcentaje_comision',
        'id_tipo_contribuyente',
        'id_rol',
        'id_estatus',
        'email',
        'correo_institucional',
        'password',
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

    // Devuelve el tipo de contribuyente del usuario.
    public function tipoContribuyente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TipoContribuyente::class, 'id_tipo_contribuyente');
    }

    // Lista los logros registrados para este personal.
    public function logrosPersonal(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LogroPersonal::class, 'id_personal');
    }
}
