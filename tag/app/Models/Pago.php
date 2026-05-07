<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pagos';

    protected $fillable = [
        'fecha_pago',
        'monto_total',
        'id_metodo_pago',
        'nro_comprobante',
        'id_tasa_cambio',
        'id_entidad_bancaria',
        'id_estado_conciliacion',
    ];
    // Devuelve la entidad bancaria asociada.
    public function entidadBancaria(): BelongsTo
    {
        return $this->belongsTo(EntidadBancaria::class, 'id_entidad_bancaria');
    }

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    // Lista las cotizaciones vinculadas a este pago.
    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(PagoOrdenCompra::class, 'id_pago');
    }

    // Devuelve el metodo de pago usado.
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }

    // Devuelve la tasa de cambio aplicada.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Devuelve el estado de conciliacion actual del pago.
    public function estadoConciliacion(): BelongsTo
    {
        return $this->belongsTo(EstadoConciliacion::class, 'id_estado_conciliacion');
    }
}
