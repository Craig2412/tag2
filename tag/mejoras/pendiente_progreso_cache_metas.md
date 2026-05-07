# Mejora Futura: `progreso_cache` + Job Nocturno en `metas_personal`

**Prioridad:** Baja — aplica cuando el volumen de `logros_personal` supere ~50,000 filas o haya más de 20 vendedores activos simultáneos.

---

## Problema Actual

El atributo `MetaPersonal::progreso_actual` calcula el progreso **en vivo en cada request**:

```php
// Se ejecuta cada vez que el dashboard pide el progreso de una meta
$query = LogroPersonal::where('id_personal', ...)
    ->where('tipo_entidad', ...)
    ->where('estatus_nuevo', ...)
    ->whereBetween('created_at', [$inicio, $fin]);
```

Si hay 30 vendedores × 5 metas cada uno = **150 consultas SQL por cada carga de dashboard**.
A escala, esto puede volverse un cuello de botella.

---

## Solución Propuesta

### 1. Nueva columna en `metas_personal`

Agregar migración:

```php
// Nueva migración: add_progreso_cache_to_metas_personal_table.php
$table->decimal('progreso_cache', 15, 2)->default(0)->comment('Progreso pre-calculado. Se actualiza por job nocturno.');
$table->timestamp('cache_calculado_at')->nullable()->comment('Última vez que se recalculó el cache.');
```

### 2. Modificar `getProgresoActualAttribute()`

```php
public function getProgresoActualAttribute()
{
    // Si el cache es reciente (menos de X horas), usarlo directamente.
    if ($this->cache_calculado_at && $this->cache_calculado_at->diffInHours(now()) < 1) {
        return $this->progreso_cache;
    }

    // Si no, calcular en vivo (fallback).
    return $this->calcularProgresoEnVivo();
}
```

### 3. Job nocturno: `RecalcularProgresoMetas`

```php
// app/Jobs/RecalcularProgresoMetas.php
class RecalcularProgresoMetas implements ShouldQueue
{
    public function handle(): void
    {
        MetaPersonal::with('meta.temporalidad')->chunk(100, function ($metas) {
            foreach ($metas as $mp) {
                $mp->update([
                    'progreso_cache'      => $mp->calcularProgresoEnVivo(),
                    'cache_calculado_at'  => now(),
                ]);
            }
        });
    }
}
```

### 4. Programar el job en `Kernel.php`

```php
// app/Console/Kernel.php
$schedule->job(new RecalcularProgresoMetas)->hourly();
// O más frecuente en horario laboral:
$schedule->job(new RecalcularProgresoMetas)->weekdays()->between('8:00', '18:00')->everyThirtyMinutes();
```

---

## Impacto

| Escenario | Sin cache | Con cache |
|:---|:---|:---|
| Dashboard 30 vendedores | 150 queries/request | 0 queries/request |
| Frecuencia de recálculo | Por request | 1 vez/hora (configurable) |
| Desfase máximo de datos | 0 seg | ~60 min (ajustable) |
| Complejidad | Baja | Media |

---

## Prerequisitos antes de implementar

- [ ] Medir el tiempo real de respuesta del dashboard con datos reales
- [ ] Confirmar que el desfase de ~1 hora es aceptable para el negocio
- [ ] Tener el scheduler de Laravel (`php artisan schedule:run`) configurado en el servidor

---

## Archivos a modificar

| Archivo | Cambio |
|:---|:---|
| `migrations/` | Nueva migración con columnas `progreso_cache` y `cache_calculado_at` |
| `Models/MetaPersonal.php` | Lógica de fallback en `getProgresoActualAttribute()` |
| `Jobs/RecalcularProgresoMetas.php` | Nuevo job |
| `Console/Kernel.php` | Programar el job |
