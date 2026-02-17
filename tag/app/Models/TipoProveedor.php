<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProveedor extends Model
{
    use HasFactory;

    protected $table = 'tipos_proveedores';

    protected $fillable = [
        'tipo_proveedor',
    ];

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'tipo_proveedor');
    }
}
