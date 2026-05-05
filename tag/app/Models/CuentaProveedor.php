<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaProveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas_proveedores';

    protected $fillable = [
        'id_proveedor',
        'numero_cuenta',
        'nombre_banco',
        'tipo_cuenta',
        'moneda',
    ];

    // Devuelve el proveedor al que pertenece la cuenta.
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
}
