<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaProveedor extends Model
{
    use HasFactory;

    protected $table = 'cuentas_proveedores';

    protected $fillable = [
        'id_proveedor',
        'numero_cuenta',
        'entidad_financiera',
        'tipo_cuenta',
        'moneda',
        'id_tipo_contribuyente',
    ];

    // Devuelve el proveedor al que pertenece la cuenta.
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    // Devuelve el tipo de contribuyente asociado.
    public function tipoContribuyente(): BelongsTo
    {
        return $this->belongsTo(TipoContribuyente::class, 'id_tipo_contribuyente');
    }
}
