<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'usuario_id',
        'user_role',
        'action',
        'table_name',
        'record_id',
        'before_data',
        'after_data',
        'ip_address',
        'user_agent',
        'route',
        'http_method',
        'status_code',
        'success',
        'message',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'success' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
