<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteEmpresa extends Model
{
    use HasFactory;

    protected $table = 'clientes_empresas';

    protected $fillable = [
        'id_cliente',
        'id_empresas',
    ];

    // Devuelve el cliente asociado.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Devuelve la empresa asociada.
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresas');
    }
}
