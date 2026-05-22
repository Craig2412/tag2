<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoProveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_proveedores';

    protected $fillable = [
        'tipo_proveedor',
    ];

    // Lista los proveedores que tienen este tipo.
    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'tipo_proveedor');
    }
}
