<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaPersonal extends Model
{
    use HasFactory;

    protected $table = 'metas_personal';

    protected $fillable = [
        'id_meta',
        'id_personal',
    ];

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class, 'id_meta');
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_personal');
    }
}
