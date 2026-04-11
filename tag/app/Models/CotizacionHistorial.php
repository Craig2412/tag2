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
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
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