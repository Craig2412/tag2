<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionHistorial extends Model
{
    use HasFactory;
    protected $table = 'cotizacion_historial';
    protected $fillable = [
        'cotizacion_id',
        'id_estado_anterior',
        'id_estado_nuevo',
        'usuario_id',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function estadoAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(EstadoCotizacion::class, 'id_estado_anterior');
    }

    public function estadoNuevoObj(): BelongsTo
    {
        return $this->belongsTo(EstadoCotizacion::class, 'id_estado_nuevo');
    }
}