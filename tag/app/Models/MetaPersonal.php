<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaPersonal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'metas_personal';

    /**
     * NOTA: Si monto, id_temporalidad, fecha_inicio, fecha_fin no existen en la tabla,
     * se debe correr la migración correspondiente antes de usar estos campos.
     */
    protected $fillable = [
        'id_meta',
        'id_personal',
        'monto',
        'id_temporalidad',
        'fecha_inicio',
        'fecha_fin',
        'es_recurrente',
    ];

    protected $casts = [
        'monto' => 'float',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'es_recurrente' => 'boolean',
    ];

    /**
     * Calcula el progreso actual de la meta basado en los logros registrados en el periodo vigente.
     */
    public function getProgresoActualAttribute()
    {
        $meta = $this->meta()->withTrashed()->first();
        if (! $meta) {
            return 0;
        }

        $temporalidad = $meta->temporalidad()->withTrashed()->first();
        if (! $temporalidad) {
            return 0;
        }

        $metodoInicio = $temporalidad->carbon_method; // ej: startOfWeek
        $metodoFin = str_replace('start', 'end', $metodoInicio); // ej: endOfWeek

        $inicio = now()->$metodoInicio();
        $fin = now()->$metodoFin();

        $query = LogroPersonal::where('id_personal', $this->id_personal)
            ->where('tipo_entidad', $meta->tipo_entidad)
            ->where('estatus_nuevo', $meta->estatus_objetivo)
            ->whereBetween('created_at', [$inicio, $fin]);

        if ($meta->es_monetario) {
            // Si es monetario, necesitamos sumar el monto de la entidad relacionada
            return $this->calcularSumaMonetaria($query, $meta);
        }

        return $query->count();
    }

    /**
     * Devuelve un arreglo con el histórico de progreso de los últimos N periodos.
     */
    public function getProgresoHistoricoAttribute(int $periodos = 6): array
    {
        $meta = $this->meta()->withTrashed()->first();
        if (! $meta) {
            return [];
        }

        $temporalidad = $meta->temporalidad()->withTrashed()->first();
        if (! $temporalidad) {
            return [];
        }

        $metodoInicio = $temporalidad->carbon_method; // ej: startOfWeek
        $metodoFin = str_replace('start', 'end', $metodoInicio); // ej: endOfWeek

        $resultado = [];

        // Para temporalidades, restamos la unidad adecuada
        $metodoSub = match (strtolower($temporalidad->slug)) {
            'diario' => 'subDays',
            'semanal' => 'subWeeks',
            'mensual' => 'subMonths',
            'anual' => 'subYears',
            default => 'subMonths',
        };

        for ($i = 0; $i < $periodos; $i++) {
            $fecha = now()->$metodoSub($i);
            $inicio = $fecha->copy()->$metodoInicio();
            $fin = $fecha->copy()->$metodoFin();

            $query = LogroPersonal::where('id_personal', $this->id_personal)
                ->where('tipo_entidad', $meta->tipo_entidad)
                ->where('estatus_nuevo', $meta->estatus_objetivo)
                ->whereBetween('created_at', [$inicio, $fin]);

            $progreso = $meta->es_monetario
                ? $this->calcularSumaMonetaria($query, $meta)
                : $query->count();

            // Formato de periodo según temporalidad
            $label = match (strtolower($temporalidad->slug)) {
                'diario' => $inicio->format('d M'),
                'semanal' => 'Semana '.$inicio->format('W Y'),
                'mensual' => $inicio->translatedFormat('M Y'),
                'anual' => $inicio->format('Y'),
                default => $inicio->format('d/m/Y'),
            };

            $resultado[] = [
                'periodo' => ucfirst($label),
                'progreso' => $progreso,
                'objetivo' => $meta->valor_objetivo,
            ];
        }

        return array_reverse($resultado); // cronológico: más antiguo primero
    }

    private function calcularSumaMonetaria($query, $meta)
    {
        // Esta lógica dependerá de cómo se llame la columna de dinero en cada entidad
        // Por ahora sumamos 'monto_total' que es común en OC y Cotizaciones
        $IdsEntidades = $query->pluck('id_entidad');

        $tabla = match ($meta->tipo_entidad) {
            'orden_compra' => 'ordenes_compra',
            'cotizacion' => 'cotizaciones',
            default => null
        };

        if (! $tabla) {
            return 0;
        }

        return \DB::table($tabla)
            ->whereIn('id', $IdsEntidades)
            ->sum('monto_total');
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class, 'id_meta');
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'id_personal');
    }
}
