# 🔀 ANÁLISIS DE REFACTORIZACIÓN DEL SISTEMA DE EVENTOS — TAG2

> **Fecha:** 18 de mayo de 2026  
> **Motivo:** Revisión de arquitectura de eventos/listeners/observers/services previa a ejecutar fixes

---

## 🗺️ GRAFO COMPLETO DE EVENTOS (trazado real)

### Eventos definidos (10)

| Evento | Quién lo dispara | Quién lo escucha |
|--------|-----------------|------------------|
| `CotizacionGuardado` | `CotizacionObserver::saved/deleted` + `Model::$dispatchesEvents` ⚠️ | `SincronizarFaseAtencionListener` + `RegistrarHistorialCotizacionListener` (💀 obsoleto) |
| `AtencionEstatusActualizado` | `AtencionController` (2x) + `EstadoFaseService` (3x) | `RegistrarHistorialEstatusAtencionListener` (auto-discovered) |
| `AtencionEtapaCambiada` | `EstadoFaseService` (1x) | `RegistrarHistorialAtencionListener` (auto-discovered) — persiste en `atencion_historial` |
| `CotizacionEstatusActualizado` | `CotizacionController` (2x) + `GenerarOrdenDesdeCotizacionListener` (1x) | `RegistrarHistorialEstatusCotizacionListener` + `GenerarOrdenDesdeCotizacionListener` (auto-discovered) |
| `OrdenCompraGuardado` | `OrdenCompraObserver::saved` | `SincronizarPadreOrdenCompraListener` + `SincronizarEstadoFinancieroListener` |
| `OrdenCompraAprobada` | `GenerarOrdenDesdeCotizacionListener` | `GenerarCuentasPorPagarListener` |
| `PagoOrdenCompraGuardado` | `Model::$dispatchesEvents` + `PagoOrdenCompraObserver` ⚠️ | `SincronizarEstadoFinancieroListener` |
| `PagoProveedorCreado` | `PagoProveedorController::store` | 🔇 **NADIE** — ¡evento huérfano! |
| `PagoProveedorEliminado` | `PagoProveedorController::destroy` | 🔇 **NADIE** — ¡evento huérfano! |
| `PermissionsUpdated` | `BroadcastPermissionsChanged` listener | Reverb broadcasting (frontend) |

---

## 🔴 HALLAZGOS GRAVES DE ARQUITECTURA (no cubiertos en informe técnico)

### RF1 · Dos eventos huérfanos — disparados pero nunca escuchados

> **[CORREGIDO 20-may-2026]:** `AtencionEtapaCambiada` **NO es huérfano**. `RegistrarHistorialAtencionListener` lo escucha vía auto-discovery y persiste el cambio de etapa en `atencion_historial`. Fue un error del análisis original. Los eventos verdaderamente huérfanos son solo 2:

| Evento | Veces disparado | Consecuencia |
|--------|----------------|-------------|
| `PagoProveedorCreado` | 1 (en `PagoProveedorController:83`) | Se dispara pero la amortización de CxP la hace el `PagoProveedorCuentaObserver`, no un listener de este evento |
| `PagoProveedorEliminado` | 1 (en `PagoProveedorController:147`) | Se dispara pero la reversión la hace `PagoProveedorObserver::deleting()`, no un listener de este evento |

**Problema real:** Estos 2 eventos se crearon con la intención de que listeners reaccionaran, pero la lógica se movió a Observers. Los eventos quedaron como ruido — se siguen disparando, consumiendo memoria y serializando modelos para nada.

---

### RF2 · `RegistrarHistorialCotizacionListener` es DEAD CODE

- **Archivo:** `app/Listeners/RegistrarHistorialCotizacionListener.php`
- **Contenido:** `public function handle(CotizacionGuardado $event): void { // OBSOLETO }`
- **Problema:** El comentario dice "se eliminó la lógica porque causaba duplicidad de eventos" — pero el listener sigue registrado vía auto-discovery. Laravel lo instancia y ejecuta su `handle()` vacío en CADA `CotizacionGuardado`. Es overhead puro.
- **Acción:** Eliminar el archivo o la clase.

---

### RF3 · Servicio disparando eventos de dominio — violación de responsabilidad

- **Archivo:** `app/Services/EstadoFaseService.php`
- **Líneas:** 59, 72, 92, 104
- **Problema:** `EstadoFaseService` es una capa de lógica de negocio. Dispara eventos de dominio:
  - `AtencionEtapaCambiada` → escuchado por `RegistrarHistorialAtencionListener` (registro de historial de etapas)
  - `AtencionEstatusActualizado` → 3 veces desde el servicio
- **Por qué es malo:** Si un listener de `AtencionEstatusActualizado` falla, el error se origina "dentro" de `EstadoFaseService`, no en el controller. Hace el debugging muy difícil. Además, `EstadoFaseService` se llama desde listeners que a su vez fueron disparados por eventos — se crean cadenas de eventos imposibles de rastrear.
- **Acción:** El servicio debe devolver información (qué cambió), y el controller o un job dedicado debe disparar los eventos.

---

### RF4 · Ciclo de recursión silencioso

```
CotizacionController::update()
  └→ $cotizacion->update() → CotizacionObserver::saved()
       └→ event(CotizacionGuardado)
            └→ SincronizarFaseAtencionListener
                 └→ EstadoFaseService::sincronizarFaseAtencion(atencion)
                      └→ event(CotizacionEstatusActualizado)  ← MANUAL en controller
                           └→ GenerarOrdenDesdeCotizacionListener
                                └→ OrdenCompra::create()
                                     └→ OrdenCompraObserver::saved()
                                          └→ event(OrdenCompraGuardado)
                                               └→ SincronizarPadreOrdenCompraListener
                                                    └→ EstadoFaseService::sincronizarFaseAtencion(atencion)  ← ¡OTRA VEZ!
```

La atención se sincroniza 2 veces en el mismo request. La segunda es redundante. Se "protege" con `saveQuietly()` pero el daño ya está hecho: queries duplicadas a catálogos, logs duplicados.

---

### RF5 · `saveQuietly` / `updateQuietly` como mecanismo de defensa

| Ubicación | Línea | Motivo real |
|-----------|-------|-------------|
| `EstadoFaseService::sincronizarFaseAtencion` | 105 | Miedo a re-disparar `AtencionEstatusActualizado` |
| `EstadoFaseService::sincronizarEstadoFinanciero` | 165 | Miedo a re-disparar `OrdenCompraGuardado` |
| `EstadoFaseService::sincronizarEstadoEgreso` | ~195 | Miedo a re-disparar `OrdenCompraGuardado` |
| `EstadoFaseService::sincronizarEstadoOperativo` | 213 | Ídem |
| `OrdenCompraObserver::deleted` | ~40 | Miedo a re-disparar eventos de Cotizacion |

**Hay 5 lugares donde el sistema dice "no le avises a nadie de este cambio".** Esto es equivalente a tener 5 agujeros en el sistema de eventos. Si en el futuro se agrega un listener que depende de `Atencion::saved`, nunca se ejecutará para estos casos.

---

### RF6 · Conflicto `Model::$dispatchesEvents` vs Observers

Dos modelos usan `$dispatchesEvents`:

```php
// Cotizacion.php
protected $dispatchesEvents = [
    'saved'   => CotizacionGuardado::class,
    'deleted' => CotizacionGuardado::class,
];

// CotizacionObserver.php
public function saved(Cotizacion $cotizacion): void {
    event(new CotizacionGuardado($cotizacion));  // ← SEGUNDA VEZ
}
```

**El mismo evento se dispara por dos mecanismos distintos.** Si alguien desregistra el Observer pensando que el modelo lo maneja, el evento sigue disparándose (y viceversa). Es una trampa de mantenibilidad.

---

### RF7 · Auto-discovery inconsistente

| Tipo | Listeners |
|------|-----------|
| Registro manual en `AppServiceProvider` | `SincronizarPadreOrdenCompraListener`, `SincronizarEstadoFinancieroListener`, `GenerarCuentasPorPagarListener`, `SincronizarFaseAtencionListener`, `BroadcastPermissionsChanged` |
| Auto-discovery por type-hint | `RegistrarHistorialEstatusAtencionListener`, `RegistrarHistorialEstatusCotizacionListener`, `GenerarOrdenDesdeCotizacionListener`, `RegistrarHistorialCotizacionListener` |

No hay criterio. Algunos listeners están en `AppServiceProvider::boot()`, otros los descubre Laravel. Si un desarrollador busca "¿quién escucha AtencionEstatusActualizado?" no lo encuentra en `AppServiceProvider` — tiene que hacer grep en todo el código.

---

### RF8 · `EstadoFaseService` es un GOD Object

Este servicio hace TODO:

- `sincronizarFaseAtencion()` — lógica de Atencion (etapas + cierre ganada/perdida)
- `sincronizarEstadoFinanciero()` — lógica de OrdenCompra ingresos
- `sincronizarEstadoEgreso()` — lógica de OrdenCompra egresos
- `sincronizarEstadoOperativo()` — lógica de OrdenCompra operativa

Son 4 responsabilidades distintas en una sola clase de ~230 líneas. Deberían ser 3 servicios separados: `AtencionStateService`, `OrdenIngresoStateService`, `OrdenEgresoStateService`.

---

### RF9 · Listeners que implementan `ShouldQueue` pero no hay worker

9 listeners implementan `ShouldQueue`. Esto significa que Laravel los encola en la tabla `jobs`. Pero:

- En desarrollo, `QUEUE_CONNECTION=sync` → se ejecutan síncronos de todos modos
- En producción, si no hay `php artisan queue:work` corriendo, los jobs se acumulan y nunca se procesan
- El sistema funciona hoy por casualidad (sync en dev), no por diseño

---

### RF10 · `LogroPersonalLogger` hookeado a `eloquent.updated: *` GLOBAL

- **Archivo:** `AppServiceProvider::boot()`
- **Problema:** CADA actualización de CUALQUIER modelo del sistema dispara `LogroPersonalLogger::logStatusChange()`. Esto incluye modelos que no son trackeables (Usuario, Cliente, TasaCambio, ConfiguracionSistema...). El método `isTrackable()` filtra, pero el evento Eloquent se dispara igual — la función se invoca, verifica, y sale. Son miles de llamadas innecesarias.

---

## 🎯 PROPUESTA DE REFACTORIZACIÓN

### Objetivo

Pasar de esto:

```
Model → Observer → event → Listener → Service → event → Listener → Service → saveQuietly
```

A esto:

```
Controller → Service (lógica pura, sin eventos) → retorna cambios
Controller → dispatch(Job) para side-effects asíncronos
Job → actualiza estados → dispatch siguiente Job si es necesario
```

### Cambios concretos

#### Fase 1: Limpiar (1 hora, bajo riesgo)

1. **Eliminar `RegistrarHistorialCotizacionListener.php`** — es dead code
2. **Eliminar `PagoProveedorCreado` y `PagoProveedorEliminado`** — son huérfanos. La lógica ya está en Observers
3. **NO eliminar `AtencionEtapaCambiada`** — NO es huérfano. `RegistrarHistorialAtencionListener` lo escucha y persiste en `atencion_historial`. [CORREGIDO 20-may-2026]
4. **Quitar `$dispatchesEvents` de `Cotizacion` y `PagoOrdenCompra`** — unificar en Observer
5. **Mover TODOS los listeners a registro explícito en `EventServiceProvider`** nuevo — eliminar auto-discovery

#### Fase 2: Desacoplar (2-3 horas, riesgo medio)

6. **`EstadoFaseService::sincronizarFaseAtencion()` no debe disparar eventos.** Debe devolver un DTO/value object con los cambios detectados:
   ```php
   $result = EstadoFaseService::sincronizarFaseAtencion($atencion);
   // $result->etapaCambiada, $result->estatusCambiado, etc.
   ```
   El controller decide si disparar eventos basado en el resultado.

7. **Dividir `EstadoFaseService` en 3 servicios:**
   - `AtencionStateService` — etapas, cierre ganada/perdida
   - `OrdenIngresoStateService` — estado financiero ingresos
   - `OrdenEgresoStateService` — estado financiero egresos

8. **Eliminar TODOS los `saveQuietly`/`updateQuietly`.** Si un cambio de estado dispara eventos, que los dispare. Los listeners deben ser idempotentes (verificar si realmente cambió algo antes de actuar).

#### Fase 3: Jobs asíncronos (2-3 horas, riesgo medio)

9. **Crear jobs dedicados para side-effects:**
   - `SincronizarFaseAtencionJob` — reemplaza a `SincronizarFaseAtencionListener`
   - `SincronizarEstadoFinancieroJob` — reemplaza a `SincronizarEstadoFinancieroListener`
   - `GenerarCuentasPorPagarJob` — reemplaza a `GenerarCuentasPorPagarListener`

10. **Los jobs se encadenan, no se anidan:**
    ```
    CotizacionAprobadaJob
      → GenerarOrdenCompraJob
        → GenerarCuentasPorPagarJob
          → SincronizarEstadoFinancieroJob
    ```
    Cada job con `$tries=3` y `backoff=5`. Si uno falla, hace retry sin afectar a los demás.

11. **Reemplazar listeners Eloquent globales por observers específicos:**
    - `LogroPersonalLogger` → solo en observers de Atencion, Cotizacion, OrdenCompra (no en `eloquent.updated: *`)
    - `AuditLogger` → mantener global pero solo para modelos en whitelist explícita

---

## 📊 COMPARATIVA ANTES / DESPUÉS

| Métrica | Actual | Propuesto |
|---------|--------|-----------|
| Eventos disparados por request | 8-14 | 3-5 |
| Listeners registrados | 10 (1 obsoleto, 2 huérfanos) | 7 (todos con propósito claro) |
| `saveQuietly` en código | 5 | 0 |
| Queries de catálogo por request | 8-15 (por duplicación) | 3-5 (cache) |
| Servicios con múltiples responsabilidades | 1 (EstadoFaseService) | 3 (separados por dominio) |
| Trazabilidad de errores | Imposible (cadenas de eventos) | Lineal (jobs encadenados) |

---

## ⚠️ Riesgos de NO refactorizar

Si solo se aplican los fixes del informe técnico (rate limiting, expiración de tokens, etc.) sin tocar la arquitectura de eventos:

1. **El próximo desarrollador que toque el sistema** no entenderá por qué hay eventos que no hacen nada, listeners vacíos, y `saveQuietly` por todos lados. Romperá algo.
2. **Agregar una nueva feature** (ej: notificaciones) requerirá engancharse a eventos que se disparan 2 veces, o a eventos que ni siquiera se disparan (por el `saveQuietly`).
3. **En producción con 100+ usuarios**, las cadenas de eventos síncronos duplicados degradarán el rendimiento notablemente.
4. **Debuggear un bug de estados** será una pesadilla — el cambio de estado puede originarse en 5 lugares distintos (controller, observer, listener, service, job).

---

## ✅ Recomendación final

**Ejecutar Fase 1 (limpieza) AHORA**, antes de cualquier otro fix. Es bajo riesgo y elimina el ruido.

**Planificar Fases 2 y 3 para después** de los fixes de seguridad y bugs del informe técnico. La Fase 2 y 3 requieren testing cuidadoso porque tocan el core del negocio.

**No desplegar a producción sin al menos la Fase 1 completada.**
