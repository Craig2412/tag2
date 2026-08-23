<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'facturas';

    protected $fillable = [
        'numero_factura',
        'id_orden_compra',
        'id_cliente',
        'emisor_rif',
        'emisor_razon_social',
        'timbrado',
        'total_gravable',
        'total_exento',
        'total_iva',
        'total_facturado',
        'total_retenciones_cliente',
        'total_retenciones_empresa',
        'total_a_pagar',
        'total_neto_empresa',
        'anio',
        'correlativo',
        'usuario_emite_id',
        'fecha_emision',
    ];

    protected $casts = [
        'total_gravable' => 'float',
        'total_exento' => 'float',
        'total_iva' => 'float',
        'total_facturado' => 'float',
        'total_retenciones_cliente' => 'float',
        'total_retenciones_empresa' => 'float',
        'total_a_pagar' => 'float',
        'total_neto_empresa' => 'float',
        'fecha_emision' => 'datetime',
    ];

    // Orden de compra origen de la factura.
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden_compra');
    }

    // Cliente al que se factura.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Detalles por servicio (congelados).
    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class, 'id_factura');
    }

    // Retenciones aplicadas (congeladas).
    public function retenciones(): HasMany
    {
        return $this->hasMany(FacturaRetencion::class, 'id_factura');
    }
}
