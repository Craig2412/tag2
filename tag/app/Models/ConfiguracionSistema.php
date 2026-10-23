<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_sistema';

    protected $fillable = [
        'dias_vencimiento',
        'tasas_sync_enabled',
        'tasas_sync_interval_minutes',
        'tasas_sync_last_run_at',
        'tasas_sync_last_status',
        'tasas_sync_last_message',
    ];

    protected $casts = [
        'tasas_sync_enabled' => 'boolean',
        'tasas_sync_interval_minutes' => 'integer',
        'tasas_sync_last_run_at' => 'datetime',
    ];
}
