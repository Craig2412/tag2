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
        'id_estado_anterior',
        'id_estado_nuevo',
        'usuario_id',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function estadoAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(EstadoOrdenCompra::class, 'id_estado_anterior');
    }

    public function estadoNuevoObj(): BelongsTo
    {
        return $this->belongsTo(EstadoOrdenCompra::class, 'id_estado_nuevo');
    }
}