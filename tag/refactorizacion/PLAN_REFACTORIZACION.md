# 📋 PLAN DE REFACTORIZACIÓN — TAG2

> **Fecha:** 18 de mayo de 2026  
> **Objetivo:** Documentar los cambios planeados, su orden de ejecución, y los resultados esperados para cada fase  
> **Archivos relacionados:** `GAPS.md` (gaps funcionales), `INFORME_TECNICO_TAG2.md` (53 hallazgos técnicos), `ANALISIS_REFACTORIZACION_EVENTOS.md` (análisis de arquitectura de eventos)

---

## 🗂️ Índice de Fases

| Fase | Nombre | Duración estimada | Riesgo | Depende de |
|------|--------|-------------------|--------|------------|
| F0 | Limpieza pre-refactorización | 1h | 🟢 Bajo | Ninguna |
| F1 | Seguridad crítica | 1h | 🟢 Bajo | F0 |
| F2 | Corrección de bugs | 2h | 🟡 Medio | F1 |
| F3 | Refactorización de eventos — desacople | 3h | 🟠 Alto | F2 |
| F4 | Refactorización de eventos — Jobs asíncronos | 3h | 🟠 Alto | F3 |
| F5 | Escalabilidad y rendimiento | 2h | 🟡 Medio | F4 |
| F6 | Pruebas del ciclo maestro | 3h | 🟢 Bajo | F5 |
| F7 | Deuda técnica y estándares | 3h | 🟡 Medio | F6 |

---

# FASE 0 (F0): LIMPIEZA PRE-REFACTORIZACIÓN

> **Objetivo:** Eliminar dead code, eventos huérfanos y listeners obsoletos antes de tocar cualquier otra cosa.

### F0.1 · Eliminar `RegistrarHistorialCotizacionListener`

| Campo | Valor |
|-------|-------|
| **Archivo a eliminar** | `app/Listeners/RegistrarHistorialCotizacionListener.php` |
| **Motivo** | Listener con `handle()` vacío. Comentario interno dice "OBSOLETO". Laravel lo instancia y ejecuta en cada `CotizacionGuardado`. |
| **Resultado esperado** | 1 listener menos. Cero impacto funcional. |

### F0.2 · Eliminar evento `PagoProveedorCreado`

| Campo | Valor |
|-------|-------|
| **Archivo a eliminar** | `app/Events/PagoProveedorCreado.php` |
| **Archivo a modificar** | `app/Http/Controllers/PagoProveedorController.php` (línea 83: quitar `event(new PagoProveedorCreado($pago))`) |
| **Motivo** | Evento huérfano. Nadie lo escucha. La lógica de amortización la hace `PagoProveedorCuentaObserver`. |
| **Resultado esperado** | 1 evento y 1 dispatch menos. Cero impacto funcional. |

### F0.3 · Eliminar evento `PagoProveedorEliminado`

| Campo | Valor |
|-------|-------|
| **Archivo a eliminar** | `app/Events/PagoProveedorEliminado.php` |
| **Archivo a modificar** | `app/Http/Controllers/PagoProveedorController.php` (línea 147: quitar `event(new PagoProveedorEliminado(...))`) |
| **Motivo** | Evento huérfano. La reversión de saldos la hace `PagoProveedorObserver::deleting()`. |
| **Resultado esperado** | 1 evento y 1 dispatch menos. Cero impacto funcional. |

### F0.4 · ~~Eliminar evento `AtencionEtapaCambiada`~~ ❌ CANCELADA

| Campo | Valor |
|-------|-------|
| **Estado** | **CANCELADA** — 20 de mayo de 2026 |
| **Motivo de cancelación** | `AtencionEtapaCambiada` **NO es huérfano**. `RegistrarHistorialAtencionListener` lo escucha vía auto-discovery y persiste el cambio de etapa en `atencion_historial`. El análisis original fue erróneo. |
| **Archivos implicados** | `app/Events/AtencionEtapaCambiada.php` (NO eliminar), `app/Services/EstadoFaseService.php:59` (NO modificar), `app/Listeners/RegistrarHistorialAtencionListener.php` (conservar) |
| **Resultado** | El historial de etapas comerciales se sigue registrando correctamente. No se toca. |

### F0.5 · Unificar emisión de `CotizacionGuardado`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Models/Cotizacion.php` (líneas 32-35: eliminar `$dispatchesEvents`) |
| **Se mantiene** | `app/Observers/CotizacionObserver.php` (única fuente de `CotizacionGuardado`) |
| **Motivo** | El evento se disparaba 2 veces: por `$dispatchesEvents` del modelo y por el Observer. |
| **Resultado esperado** | `CotizacionGuardado` se dispara 1 vez. `SincronizarFaseAtencionListener` y `SincronizarEstadoFinancieroListener` se ejecutan 1 vez. |

### F0.6 · Unificar camino de `PagoOrdenCompraGuardado`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Models/PagoOrdenCompra.php` (líneas 20-23: eliminar `$dispatchesEvents`) |
| **Archivo a modificar** | `app/Observers/PagoOrdenCompraObserver.php` (línea 17-19: eliminar llamada directa a `EstadoFaseService::sincronizarEstadoFinanciero()` — el listener `SincronizarEstadoFinancieroListener` ya lo hace al escuchar el evento) |
| **Motivo** | **[CORREGIDO 20-may-2026]:** El Observer NO dispara el evento 2 veces (solo el modelo lo hace vía `$dispatchesEvents`). Pero el Observer llama a `EstadoFaseService::sincronizarEstadoFinanciero()` **directamente**, y el listener también lo llama al escuchar `PagoOrdenCompraGuardado`. Resultado: `sincronizarEstadoFinanciero()` se ejecuta 2 veces. Hay que unificar en UN solo camino. |
| **Se mantiene** | `app/Listeners/SincronizarEstadoFinancieroListener.php` como ÚNICO punto que llama a `EstadoFaseService::sincronizarEstadoFinanciero()` para órdenes. El Observer solo debe disparar el evento. |
| **Resultado esperado** | `EstadoFaseService::sincronizarEstadoFinanciero()` se ejecuta 1 vez. |

### F0.7 · Mover registro de listeners a `EventServiceProvider`

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `app/Providers/EventServiceProvider.php` |
| **Archivo a modificar** | `app/Providers/AppServiceProvider.php` (extraer `Event::listen(...)` al nuevo provider) |
| **Motivo** | Separar responsabilidades. `AppServiceProvider` tiene 130+ líneas mezclando observers, eventos y audit. |
| **Resultado esperado** | `EventServiceProvider` con mapeo `Event => [Listeners]`. `AppServiceProvider` solo con observers y audit hooks. |

### F0.8 · Crear directorio `refactorizacion/`

| Campo | Valor |
|-------|-------|
| **Archivos a mover** | `GAPS.md`, `INFORME_TECNICO_TAG2.md`, `ANALISIS_REFACTORIZACION_EVENTOS.md`, `PLAN_REFACTORIZACION.md` |
| **Ubicación destino** | `refactorizacion/` en raíz del proyecto |
| **Resultado esperado** | Documentación de refactorización centralizada en un solo directorio. |

---

# FASE 1 (F1): SEGURIDAD CRÍTICA

> **Objetivo:** Cerrar los agujeros de seguridad explotables sin romper funcionalidad.

### F1.1 · Rate limiting en endpoints de autenticación

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `routes/api.php` |
| **Cambio** | Agregar `->middleware('throttle:10,1')` a `/login`, `/login/admin`, `/login/user`, `/register` |
| **Resultado esperado** | Máximo 10 intentos de login por minuto por IP. Respuesta 429 Too Many Requests al exceder. |

### F1.2 · Expiración de tokens Sanctum

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `config/sanctum.php` |
| **Cambio** | `'expiration' => 1440` (24 horas) o `43200` (30 días) según criterio de negocio |
| **Resultado esperado** | Tokens expiran automáticamente. Usuario debe re-autenticarse. |

### F1.3 · Eliminar `APP_DEBUG=true` del `.env` por defecto

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `.env` |
| **Cambio** | `APP_DEBUG=false` en `.env`. Crear `.env.example` con `APP_DEBUG=true` para desarrollo. |
| **Resultado esperado** | Si se despliega sin cambiar el `.env`, no expone stack traces. |

### F1.4 · Completar `.env.example`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `.env.example` (YA EXISTE, pero está incompleto) |
| **Contenido** | Agregar claves faltantes: `REDIS_HOST`, `REDIS_CLIENT`, `REDIS_PORT`, `DB_CONNECTION=mysql` (actualmente dice `sqlite`), y demás variables del `.env` real con valores dummy. |
| **Resultado esperado** | `composer setup` funciona correctamente. Nuevos desarrolladores tienen todas las variables necesarias para replicar el entorno. **[CORREGIDO 20-may-2026: el archivo ya existe, no hay que crearlo sino completarlo]** |

### F1.5 · `SESSION_ENCRYPT=true`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `.env` |
| **Cambio** | `SESSION_ENCRYPT=true` |
| **Resultado esperado** | Cookies de sesión viajan cifradas. |

---

# FASE 2 (F2): CORRECCIÓN DE BUGS

> **Objetivo:** Arreglar bugs que afectan la funcionalidad o integridad de datos.

### F2.1 · Quitar IDs hardcodeados de estado financiero

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Listeners/GenerarOrdenDesdeCotizacionListener.php` |
| **Cambio** | Reemplazar `'id_estado_financiero' => 1` y `'id_estado_financiero_egreso' => 1` por consulta dinámica `EstadoFinanciero::where('slug', 'pendiente')->firstOrFail()->id` |
| **Resultado esperado** | La OC se crea con el estado "pendiente" correcto sin importar el orden de IDs en el catálogo. |

### F2.2 · ~~Corregir rol `cliente` inexistente~~ ❌ CANCELADA

| Campo | Valor |
|-------|-------|
| **Estado** | **CANCELADA** — 20 de mayo de 2026 |
| **Motivo de cancelación** | El rol `cliente` **SÍ existe**. Se crea en `RoleSeeder.php:156` mediante `Role::findOrCreate('cliente', 'web')`. La asignación `assignRole('cliente')` en `ClienteService.php:23` es semánticamente correcta. El análisis original asumió erróneamente que el rol no existía. |
| **Acción real** | Verificar que los seeders se ejecuten como parte del setup del proyecto. Si `php artisan db:seed` se ha ejecutado, el rol existe y la asignación funciona. No cambiar a `'user'`. |

### F2.3 · Limpiar campos fantasma en `TestMasterCommercialCycle`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Console/Commands/TestMasterCommercialCycle.php` |
| **Cambio** | Quitar `'referencia'` de `Cotizacion::create()`. Quitar `'id_usuario'` de `PagoProveedor::create()`. |
| **Resultado esperado** | `php artisan app:test-master` no lanza errores de campos desconocidos. |

### F2.4 · Unificar borrado de `PagoProveedor`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Http/Controllers/PagoProveedorController.php` |
| **Cambio** | Eliminar `$pagoProveedor->cuentasPorPagar()->detach()` del `destroy()`. Dejar solo `$pagoProveedor->delete()` — el Observer `PagoProveedorObserver::deleting()` ya maneja la reversión. |
| **Resultado esperado** | Una sola lógica de borrado. Saldos consistentes. |

### F2.5 · Corregir `MetricasController` — propiedad inexistente

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Http/Controllers/MetricasController.php` |
| **Cambio** | `$row->estatusNuevoObj->estatus` → buscar el slug del `EstadoFinanciero` correctamente usando el ID de la relación. |
| **Resultado esperado** | `promedioTiempoPagoOrden` devuelve valores reales. |

### F2.6 · `str_replace` → `Storage::url()` en `PagoController`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Http/Controllers/PagoController.php` |
| **Cambio** | `str_replace('public/', 'storage/', $rutaComprobante)` → `Storage::url($rutaComprobante)` |
| **Resultado esperado** | URLs de comprobantes generadas correctamente en cualquier filesystem. |

### F2.7 · `config/queue.php`: `after_commit => true`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `config/queue.php` |
| **Cambio** | `'after_commit' => true` |
| **Resultado esperado** | Jobs solo se despachan tras confirmar la transacción. Sin datos fantasma. |

---

# FASE 3 (F3): REFACTORIZACIÓN DE EVENTOS — DESACOPLE

> **Objetivo:** Eliminar `saveQuietly`, sacar eventos de `EstadoFaseService`, dividir el GOD object.

### F3.1 · `EstadoFaseService` deja de disparar eventos

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Services/EstadoFaseService.php` |
| **Cambio** | Los 4 métodos (`sincronizarFaseAtencion`, `sincronizarEstadoFinanciero`, `sincronizarEstadoEgreso`, `sincronizarEstadoOperativo`) devuelven un DTO con los cambios detectados en vez de disparar eventos. |
| **Nuevo archivo** | `app/DTOs/CambioEstado.php` — value object con campos: `huboCambio`, `estadoAnterior`, `estadoNuevo`, `comentario`. |
| **Resultado esperado** | El servicio es una función pura: recibe modelo, devuelve cambios. El controller decide si disparar eventos. |

### F3.2 · Dividir `EstadoFaseService` en 3 servicios

| Campo | Valor |
|-------|-------|
| **Archivos a crear** | `app/Services/AtencionStateService.php`, `app/Services/OrdenIngresoStateService.php`, `app/Services/OrdenEgresoStateService.php` |
| **Archivo a refactorizar** | `app/Services/EstadoFaseService.php` → extraer métodos a los 3 nuevos servicios. El original queda como fachada que delega (para no romper imports existentes). |
| **Resultado esperado** | Cada servicio ~60 líneas, una sola responsabilidad. Testeables por separado. |

### F3.3 · Eliminar TODOS los `saveQuietly`/`updateQuietly`

| Campo | Valor |
|-------|-------|
| **Archivos a modificar** | `app/Services/EstadoFaseService.php` (5 ocurrencias), `app/Observers/OrdenCompraObserver.php` (1 ocurrencia) |
| **Cambio** | Reemplazar por `save()`/`update()` normales. Los listeners deben verificar si realmente cambió el estado antes de actuar (idempotencia). |
| **Resultado esperado** | El sistema de eventos funciona de forma predecible. No hay cambios de estado "silenciosos". |

### F3.4 · `LogroPersonalLogger` hookeado solo a modelos trackeables

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Providers/AppServiceProvider.php` |
| **Cambio** | En vez de `eloquent.updated: *`, registrar observers específicos para Atencion, Cotizacion, OrdenCompra que invoquen `LogroPersonalLogger`. |
| **Resultado esperado** | Miles de llamadas innecesarias eliminadas. Solo 3 modelos disparan logro personal. |

---

# FASE 4 (F4): REFACTORIZACIÓN DE EVENTOS — JOBS ASÍNCRONOS

> **Objetivo:** Reemplazar listeners anidados por Jobs encadenados.

### F4.1 · Crear `SincronizarFaseAtencionJob`

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `app/Jobs/SincronizarFaseAtencionJob.php` |
| **Reemplaza a** | `SincronizarFaseAtencionListener` |
| **Comportamiento** | `ShouldQueue`, `$tries=3`, `backoff=5`. Recibe `Atencion`, ejecuta `AtencionStateService::sincronizarFase()`. |
| **Resultado esperado** | La sincronización de fase no bloquea el request HTTP. Si falla, hace retry. |

### F4.2 · Crear `GenerarOrdenCompraJob`

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `app/Jobs/GenerarOrdenCompraJob.php` |
| **Reemplaza a** | `GenerarOrdenDesdeCotizacionListener` |
| **Comportamiento** | Recibe `Cotizacion`. Crea `OrdenCompra`. Al terminar, despacha `GenerarCuentasPorPagarJob`. |
| **Resultado esperado** | Generación de OC asíncrona. Si falla, la cotización queda aprobada pero sin OC — el job hace retry. |

### F4.3 · Crear `GenerarCuentasPorPagarJob`

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `app/Jobs/GenerarCuentasPorPagarJob.php` |
| **Reemplaza a** | `GenerarCuentasPorPagarListener` |
| **Comportamiento** | Recibe `OrdenCompra`. Agrupa servicios por proveedor, crea CxP. Al terminar, despacha `SincronizarEstadoEgresoJob`. |
| **Resultado esperado** | CxP generadas asíncronamente. |

### F4.4 · Crear `SincronizarEstadoFinancieroJob` y `SincronizarEstadoEgresoJob`

| Campo | Valor |
|-------|-------|
| **Archivos a crear** | `app/Jobs/SincronizarEstadoFinancieroJob.php`, `app/Jobs/SincronizarEstadoEgresoJob.php` |
| **Reemplazan a** | `SincronizarEstadoFinancieroListener` (que manejaba ambos `OrdenCompraGuardado` y `PagoOrdenCompraGuardado`) |
| **Resultado esperado** | Cada job tiene una sola responsabilidad. Se encadenan: pago registrado → recalcular estado financiero → si está completo, marcar OC completada. |

---

# FASE 5 (F5): ESCALABILIDAD Y RENDIMIENTO

> **Objetivo:** Preparar el sistema para 100+ usuarios concurrentes.

### F5.1 · Refactorizar `MetricasController`

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Http/Controllers/MetricasController.php` |
| **Cambio** | Reemplazar `->get()` en memoria por queries SQL agregadas (`AVG(TIMESTAMPDIFF(...))`, `COUNT(DISTINCT ...)`). |
| **Resultado esperado** | Métricas en < 100ms con 100,000 registros. Sin riesgo de OOM. |

### F5.2 · Agregar índices compuestos

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `database/migrations/xxxx_add_compound_indexes.php` |
| **Índices** | `cotizaciones(id_atencion, id_estado_cotizacion)`, `atenciones(id_personal, id_estado_atencion)`, `ordenes_compra(id_estado_financiero, id_estado_financiero_egreso)`, `logros_personal(id_personal, created_at)`, `pago_proveedor_cuentas(id_cuenta_por_pagar, id_pago_proveedor)` |
| **Resultado esperado** | Queries de sincronización de estados 5-10x más rápidas. |

### F5.3 · Migrar caché a Redis

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `.env` |
| **Cambio** | `CACHE_STORE=redis` |
| **Resultado esperado** | La caché no compite con MySQL por recursos. |

### F5.4 · Cachear catálogos

| Campo | Valor |
|-------|-------|
| **Archivos a modificar** | `EstadoFaseService`, `GenerarOrdenDesdeCotizacionListener`, `GenerarCuentasPorPagarListener` |
| **Cambio** | Envolver consultas a `EstadoFinanciero::where('slug', ...)->first()` en `Cache::rememberForever()`. Invalidar caché en seeders. |
| **Resultado esperado** | ~8 queries de catálogo eliminadas por request. |

### F5.5 · Política de retención de `audit_logs` y `logros_personal`

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `app/Console/Commands/PruneOldLogs.php` |
| **Registro en scheduler** | `routes/console.php`: `$schedule->command('app:prune-old-logs')->daily()` |
| **Resultado esperado** | Tablas de auditoría no crecen indefinidamente. Se eliminan registros > 90 días. |

---

# FASE 6 (F6): PRUEBAS DEL CICLO MAESTRO

> **Objetivo:** Cobertura de tests para el flujo de negocio principal.

### F6.1 · `CommercialCycleTest` — flujo completo feliz

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `tests/Feature/CommercialCycleTest.php` |
| **Escenario** | Atención → Cotización creada → Cotización aprobada → OC generada → CxP generada → Pago cliente → Pago proveedor → Verificar estados finales |
| **Assertions** | `id_estado_financiero = pagado`, `id_estado_financiero_egreso = pagado`, `id_estado_orden_compra = completada`, `saldo_pendiente CxP = 0` |

### F6.2 · `CommercialCycleTest` — soft-delete cascada

| Campo | Valor |
|-------|-------|
| **Escenario** | Crear ciclo completo → soft-delete PagoProveedor → verificar reversión de saldos CxP → soft-delete Pago → verificar estado financiero OC vuelve a pendiente |

### F6.3 · `CommercialCycleTest` — rechazo total de cotizaciones

| Campo | Valor |
|-------|-------|
| **Escenario** | Atención con 2 cotizaciones → rechazar ambas → verificar atención cerrada_perdida |

### F6.4 · `EventSystemTest` — no duplicación

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `tests/Feature/EventSystemTest.php` |
| **Escenario** | Crear cotización → verificar que `CotizacionGuardado` se disparó exactamente 1 vez → verificar que `SincronizarFaseAtencionListener` se ejecutó 1 vez |

---

# FASE 7 (F7): DEUDA TÉCNICA Y ESTÁNDARES

> **Objetivo:** Alinear el código con convenciones Laravel y buenas prácticas.

### F7.1 · Crear Policies para todos los recursos

| Campo | Valor |
|-------|-------|
| **Archivos a crear** | `app/Policies/CotizacionPolicy.php`, `app/Policies/OrdenCompraPolicy.php`, `app/Policies/AtencionPolicy.php`, `app/Policies/PagoPolicy.php` |
| **Reglas** | `viewAny`: cualquier autenticado. `view`: dueño o admin. `create`: cualquier autenticado. `update`: dueño o admin. `delete`: solo admin. `approve`: solo admin (para cotizaciones/OC). |
| **Resultado esperado** | Control granular. Un `personal` no puede modificar recursos de otro `personal`. |

### F7.2 · Formato de error envelope estándar

| Campo | Valor |
|-------|-------|
| **Archivo a modificar** | `app/Exceptions/Handler.php` (o `bootstrap/app.php`) |
| **Formato** | `{ "success": false, "message": "...", "errors": { "campo": ["..."] } }` |
| **Resultado esperado** | Frontend recibe siempre el mismo formato de error. |

### F7.3 · Estandarizar nombres de tablas (inglés, plural)

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `database/migrations/xxxx_rename_tables_to_english.php` |
| **Renombrados** | `pagos_a_proveedores` → `provider_payments`, `personal` → `personnel`, `tipo_servicio` → `service_types`, `atenciones` → `attendances`, `cotizaciones` → `quotations`, `ordenes_compra` → `purchase_orders` |
| **Archivos a modificar** | Todos los modelos con `$table` explícito. |
| **Resultado esperado** | Tablas siguen convención Laravel. `$table` ya no es necesario en la mayoría de modelos. |

### F7.4 · Documentar arquitectura

| Campo | Valor |
|-------|-------|
| **Archivo a crear** | `docs/arquitectura.md` |
| **Contenido** | Diagrama de flujo de eventos, decisiones de diseño (ADR), justificación de por qué Jobs en vez de Listeners, guía para agregar nuevos eventos. |
| **Resultado esperado** | Cualquier desarrollador nuevo entiende la arquitectura en 15 minutos. |

---

# 📊 RESUMEN DE RESULTADOS ESPERADOS POR FASE

| Fase | Eventos disparados/request | Queries catálogo/request | `saveQuietly` | Cobertura tests | Nota sistema |
|------|---------------------------|-------------------------|---------------|-----------------|-------------|
| **Antes** | 8-14 (2-3 duplicados) | 8-15 (sin cache) | 5 | < 2% | 5.1/10 |
| **F0** | 5-9 (sin duplicados) | 8-15 | 5 | < 2% | 5.5/10 |
| **F1** | 5-9 | 8-15 | 5 | < 2% | 6.0/10 |
| **F2** | 5-9 | 8-15 | 5 | < 2% | 6.5/10 |
| **F3** | 4-6 | 5-8 | 0 | < 2% | 7.0/10 |
| **F4** | 2-4 (asíncronos) | 5-8 | 0 | < 2% | 7.5/10 |
| **F5** | 2-4 | 1-3 (cache) | 0 | < 2% | 8.0/10 |
| **F6** | 2-4 | 1-3 | 0 | ~70% | 8.5/10 |
| **F7** | 2-4 | 1-3 | 0 | ~70% | 9.0/10 |

---

# ⚠️ Notas importantes

1. **Cada fase es atómica.** Se completa, se testea con `php artisan app:test-master`, y se confirma antes de pasar a la siguiente.
2. **F0 debe ejecutarse antes que cualquier otra.** Es la limpieza que permite ver el sistema real sin ruido.
3. **F3 y F4 son las de mayor riesgo.** Tocar el core de eventos puede romper el flujo maestro. Se necesita testing manual exhaustivo después de cada una.
4. **F7.3 (renombrar tablas) es opcional.** Si el sistema ya tiene datos en producción, requiere migración con downtime. Se puede posponer para v2.
5. **Los nombres de clases, métodos y archivos en este documento son los NOMBRES FINALES.** No son placeholders. Si se implementa, se usan exactamente estos nombres.

---

# 📝 CORRECCIONES APLICADAS (20 de mayo de 2026)

Tras verificación exhaustiva del código fuente real, se detectaron y corrigieron los siguientes errores en los documentos originales:

| # | Error en documento original | Corrección aplicada | Impacto en el plan |
|---|---------------------------|---------------------|-------------------|
| 1 | **F0.4** — `AtencionEtapaCambiada` reportado como huérfano | **CANCELADA.** `RegistrarHistorialAtencionListener` SÍ lo escucha y persiste en `atencion_historial`. | No eliminar el evento ni su dispatch. |
| 2 | **F2.2 / B4** — Rol `cliente` reportado como inexistente | **CANCELADA.** El rol SÍ existe en `RoleSeeder.php:156` (`findOrCreate('cliente', 'web')`). | No cambiar `assignRole('cliente')` → `assignRole('user')`. |
| 3 | **F1.4 / P5** — `.env.example` reportado como no existente | **CORREGIDO.** El archivo SÍ existe pero está incompleto. | Completar en vez de crear. |
| 4 | **B2** — `PagoOrdenCompraGuardado` reportado como "disparado 2 veces" | **CORREGIDO.** No se dispara 2 veces; el Observer llama al servicio directamente + el listener también. | Mecanismo de unificación ajustado (ver F0.6). |
| 5 | **RF1** — "Tres eventos huérfanos" en ANALISIS_REFACTORIZACION_EVENTOS.md | **CORREGIDO.** Son 2 eventos huérfanos, no 3. `AtencionEtapaCambiada` tiene listener. | Solo eliminar `PagoProveedorCreado` y `PagoProveedorEliminado`. |
| 6 | **RF3** — `AtencionEtapaCambiada` listado como "nadie lo escucha" | **CORREGIDO.** Sí es escuchado por `RegistrarHistorialAtencionListener`. | El servicio sí tiene razón para dispararlo. |
