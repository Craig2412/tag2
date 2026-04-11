<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraHistorial extends Model
{
    use HasFactory;
    protected $table = 'orden_compra_historial';
    protected $fillable = [
        'orden_compra_id',
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function estatusAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus_anterior');
    }

    public function estatusNuevoObj(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus_nuevo');
    }
}