# Pendiente: Migración de `Logros de Personal` y `Metas` fuera de la tabla `estatus`

## Contexto

Como parte del proceso de desacoplamiento de la tabla global `estatus` (ver historial de conversaciones), los siguientes módulos son los **únicos dos que aún dependen** de esa tabla problemática.

Todos los demás módulos ya fueron migrados:
- ✅ Cuentas por Pagar → `estados_financieros`
- ✅ Pagos a Proveedores → campo eliminado (uso de SoftDeletes)
- ✅ Pagos de Clientes → `estados_conciliacion`
- ✅ Atenciones, Cotizaciones, Órdenes de Compra → ya limpios

---

## Módulos Pendientes

### 1. `logros_personal` — Historial de cambios de estatus de tickets

**Problema actual:**
La tabla `logros_personal` registra cada vez que un miembro del personal cambia el estado de un ticket (Atención, Cotización u Orden de Compra). Para eso guarda el estatus anterior y el nuevo, ambos como FK a la tabla genérica `estatus`.

```php
// app/Models/LogroPersonal.php
'id_estatus_anterior',  // FK -> estatus (tabla genérica)
'id_estatus_nuevo',     // FK -> estatus (tabla genérica)
```

```php
// database/migrations/2026_03_26_000000_create_logros_personal_table.php
$table->foreignId('id_estatus_anterior')->nullable()->constrained('estatus');
$table->foreignId('id_estatus_nuevo')->constrained('estatus');
```

**Problema de diseño:**
`logros_personal` usa `tipo_entidad` (ej: `atencion`, `cotizacion`, `orden_compra`) para saber a qué módulo pertenece el cambio. Sin embargo, los IDs de estatus que guarda son de la tabla genérica. Esto significa que si se migran los estatus de Atenciones a su propia tabla, los logros quedan apuntando a IDs inválidos o ambiguos.

**Solución propuesta:**

**Opción A — Guardar el texto del estatus (recomendada):**
En lugar de guardar FKs frágiles, guardar directamente el nombre del estado como `string`. Ejemplo:
```php
$table->string('estatus_anterior', 50)->nullable(); // Ej: "Por Aprobar"
$table->string('estatus_nuevo', 50);               // Ej: "Aprobado"
```
- ✅ Inmune a cambios de IDs.
- ✅ Funciona independientemente del módulo (`tipo_entidad`).
- ✅ Los logros son registros históricos: el nombre del estado es suficiente.
- ⚠️ Se pierde la relación Eloquent directa, pero se gana consistencia.

**Opción B — FK polimórfica por módulo:**
Mantener FKs pero apuntar a la tabla de estados correcta según `tipo_entidad`. Complejo de implementar y mantener.

---

**Archivos a modificar:**

| Archivo | Cambio |
|:---|:---|
| `database/migrations/2026_03_26_000000_create_logros_personal_table.php` | Reemplazar `foreignId('id_estatus_anterior/nuevo')->constrained('estatus')` por `string('estatus_anterior/nuevo')` |
| `app/Models/LogroPersonal.php` | Eliminar `id_estatus_anterior`, `id_estatus_nuevo` del `$fillable`; agregar `estatus_anterior`, `estatus_nuevo`. Eliminar las relaciones `estatusAnterior()` y `estatusNuevo()` |
| Listeners/Observers que escriben logros | Pasar el nombre del estado en lugar del ID. Buscar con: `grep -r "LogroPersonal::create" app/` |
| Resource de LogroPersonal (si existe) | Actualizar los campos devueltos |

---

### 2. `metas` — Objetivo basado en un estatus de la tabla genérica

**Problema actual:**
Una `Meta` define un hito a alcanzar (ej: "X cotizaciones confirmadas en el mes"). Para saber si el hito se cumplió, compara el estatus de los registros contra un `id_estatus_objetivo` que apunta a la tabla `estatus`.

```php
// app/Models/Meta.php
'id_estatus_objetivo',  // FK -> estatus (tabla genérica)

public function estatusObjetivo(): BelongsTo
{
    return $this->belongsTo(Estatus::class, 'id_estatus_objetivo');
}
```

```php
// database/migrations/2026_03_01_000001_create_metas_table.php
$table->foreignId('id_estatus_objetivo')->constrained('estatus')
      ->comment('El estatus que marca el hito logrado');
```

**Lógica de evaluación (en MetaPersonal.php):**
```php
// Compara logros del personal contra el id_estatus_objetivo de la meta
->where('id_estatus_nuevo', $meta->id_estatus_objetivo)
```

**Problema de diseño:**
La lógica de evaluación de metas asume que todos los estatus viven en la misma tabla. Si se migran los estados de Cotizaciones o Atenciones a tablas propias, esta comparación se rompe porque estaría comparando IDs de tablas distintas.

**Solución propuesta:**

**Opción A — Guardar el slug del estado objetivo como string (recomendada):**
```php
$table->string('tipo_entidad');          // Ej: 'cotizacion'
$table->string('estatus_objetivo', 50);  // Ej: 'confirmado'
```
La lógica de evaluación entonces compara el texto del estado logrado vs el texto del objetivo. Es más legible y desacoplada.

**Opción B — Crear un catálogo unificado de "estados de hitos":**
Tabla `estados_metas` con los estados que pueden ser objetivo. Más formal, pero más trabajo.

---

**Archivos a modificar:**

| Archivo | Cambio |
|:---|:---|
| `database/migrations/2026_03_01_000001_create_metas_table.php` | Reemplazar `foreignId('id_estatus_objetivo')->constrained('estatus')` por `string('estatus_objetivo', 50)` |
| `app/Models/Meta.php` | Cambiar `id_estatus_objetivo` por `estatus_objetivo` en `$fillable`. Eliminar la relación `estatusObjetivo()` |
| `app/Models/MetaPersonal.php` | Actualizar la comparación: `->where('id_estatus_nuevo', ...)` → `->where('estatus_nuevo', $meta->estatus_objetivo)` |
| Controller/Resource de Meta (si existen) | Actualizar campos de entrada/salida |

---

## Tabla Resumen

| Módulo | Tabla | Columnas afectadas | Solución | Prioridad |
|:---|:---|:---|:---|:---|
| **Logros de Personal** | `logros_personal` | `id_estatus_anterior`, `id_estatus_nuevo` | Cambiar a `string` con nombre del estado | Media |
| **Metas** | `metas` | `id_estatus_objetivo` | Cambiar a `string` con slug del estado | Media |

---

## Decisión de arquitectura: No se crea una tabla de estados propia

`logros_personal` y `metas` **NO necesitan su propia tabla de estados**. En cambio, consumen los slugs de las tablas de estados por dominio ya existentes (`estados_atenciones`, `estados_cotizaciones`, `estados_ordenes_compra`).

El flujo es el siguiente:

```
estados_atenciones     ──┐
estados_cotizaciones   ──┼──► definen slugs (ej: "aprobado", "confirmado")
estados_ordenes_compra ──┘         │
                                   │  El Listener/Observer copia el slug al registrar el logro
                                   ▼
                     logros_personal.estatus_nuevo    = "aprobado"
                     logros_personal.estatus_anterior = "por_aprobar"
                                   │
                                   │  MetaPersonal compara texto vs texto
                                   ▼
                     metas.estatus_objetivo           = "aprobado"
                     ¿logro.estatus_nuevo == meta.estatus_objetivo? ✅
```

**Regla importante:** Los slugs en las tablas de dominio deben tratarse como **valores inmutables** una vez creados. Si se renombra un slug (ej: `aprobado` → `aceptado`), los logros históricos y metas antiguas quedarían con el valor desactualizado.

---

## Consideraciones antes de ejecutar

1. **Los estatus de Atenciones, Cotizaciones y Órdenes de Compra ya están limpios** — sus slugs son la fuente de verdad que consumirán Logros y Metas.
2. **Logros son registros históricos** — al hacer `migrate:fresh` los datos se pierden de todas formas. En producción se necesitaría un script de migración de datos.
3. **Orden sugerido de ejecución:** Metas → Logros (porque Logros depende de que los slugs de estado estén claros).
4. **Los slugs son inmutables** — una vez definidos en las tablas de dominio, no deben renombrarse.
