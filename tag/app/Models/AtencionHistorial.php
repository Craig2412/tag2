<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtencionHistorial extends Model
{
    use HasFactory;
    protected $table = 'atencion_historial';
    protected $fillable = [
        'atencion_id',
        'id_estado_anterior',
        'id_estado_nuevo',
        'id_etapa_anterior',
        'id_etapa_nueva',
        'usuario_id',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'atencion_id');
    }

    public function estadoAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(EstadoAtencion::class, 'id_estado_anterior');
    }

    public function estadoNuevoObj(): BelongsTo
    {
        return $this->belongsTo(EstadoAtencion::class, 'id_estado_nuevo');
    }

    public function etapaAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(EtapaComercial::class, 'id_etapa_anterior');
    }

    public function etapaNuevaObj(): BelongsTo
    {
        return $this->belongsTo(EtapaComercial::class, 'id_etapa_nueva');
    }
}