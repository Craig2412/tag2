# 🏗️ Arquitectura del Sistema de Eventos — TAG2

> **Fecha:** 20 de mayo de 2026  
> **Stack:** Laravel 12 · PHP 8.2 · MySQL · Sanctum · Spatie Permissions · Redis

---

## 🗺️ Diagrama de Flujo de Eventos

```
┌─────────────────────────────────────────────────────────────────┐
│                    CICLO MAESTRO COMERCIAL                       │
└─────────────────────────────────────────────────────────────────┘

Controller                                Observers/Services
─────────                                ─────────────────
                                                    
AtencionController::store                 
  └→ Atencion::create()                  
                                                    
CotizacionController::store              
  └→ Cotizacion::create()               
       └→ CotizacionObserver::saved()    
            └→ event(CotizacionGuardado) ──→ SincronizarFaseAtencionListener
                                                 └→ EstadoFaseService::sincronizarFaseAtencion()
                                                      └→ retorna DTO CambioEstado
                                                 └→ event(AtencionEtapaCambiada)
                                                 └→ event(AtencionEstatusActualizado)

CotizacionController::update(estatus=aprobada)
  └→ event(CotizacionEstatusActualizado)
       └→ GenerarOrdenDesdeCotizacionListener
            └→ OrdenCompra::create()
                 └→ OrdenCompraObserver::saved()
                      └→ event(OrdenCompraGuardado)
                           └→ SincronizarEstadoFinancieroListener
                           └→ SincronizarPadreOrdenCompraListener
            └→ event(OrdenCompraAprobada)
                 └→ GenerarCuentasPorPagarListener
                      └→ CuentaPorPagar::create()
                      └→ EstadoFaseService::sincronizarEstadoEgreso()

PagoController::store
  └→ PagoOrdenCompra::create()
       └→ PagoOrdenCompraObserver::saved()
            └→ event(PagoOrdenCompraGuardado)
                 └→ SincronizarEstadoFinancieroListener
                      └→ EstadoFaseService::sincronizarEstadoFinanciero()
                           └→ EstadoFaseService::sincronizarEstadoOperativo()

PagoProveedorController::store
  └→ PagoProveedorCuenta::create()
       └→ PagoProveedorCuentaObserver::created()
            └→ actualiza saldo CxP
            └→ EstadoFaseService::sincronizarEstadoEgreso()
                 └→ EstadoFaseService::sincronizarEstadoOperativo()
```

---

## 📋 Registro de Decisiones de Arquitectura (ADR)

### ADR-001: Observers como única fuente de emisión de eventos

**Decisión:** Los eventos de dominio son disparados exclusivamente por Observers de Eloquent, nunca por `$dispatchesEvents` del modelo ni por servicios.

**Motivo:** Unificar el mecanismo de emisión evita disparos duplicados (antes `CotizacionGuardado` se disparaba 2 veces: por el modelo y por el Observer).

**Implementado:** 20-may-2026 (Fase 1).

---

### ADR-002: Servicios no disparan eventos — retornan DTOs

**Decisión:** `EstadoFaseService` y futuros servicios de estado retornan un DTO `CambioEstado` en vez de disparar eventos internamente. Los callers (listeners/observers) deciden si disparar eventos.

**Motivo:** Separar lógica de negocio pura de side-effects. Un servicio que dispara eventos es difícil de testear y debuggear. El DTO hace explícito qué cambió.

**Implementado:** 20-may-2026 (Fase 3).

---

### ADR-003: `save()` normal, sin `saveQuietly()`

**Decisión:** Todos los saves/updates usan los métodos normales de Eloquent. Los listeners son idempotentes (verifican si el estado realmente cambió antes de actuar).

**Motivo:** `saveQuietly()` creaba agujeros en el sistema de eventos — cambios de estado que nadie escuchaba. Con listeners idempotentes, el overhead de re-ejecución es mínimo (una ronda extra de queries que retornan temprano).

**Implementado:** 20-may-2026 (Fase 3).

---

### ADR-004: Catálogos cacheados con TTL de 24h

**Decisión:** Consultas a catálogos del core (`EstadoFinanciero`, `EstadoAtencion`, `EstadoCotizacion`, `EstadoOrdenCompra`, `EtapaComercial`) se cachean con `Cache::remember(key, 86400)`.

**Motivo:** Los slugs de estos catálogos son constantes de negocio (`pendiente`, `pagado`, `cerrada_ganada`). Cambiarlos rompería la máquina de estados independientemente del cache. TTL de 24h como red de seguridad.

**Invalidación:** `DatabaseSeeder` ejecuta `Cache::flush()` al terminar.

**Implementado:** 20-may-2026 (Fase 5).

---

### ADR-005: Policies por permiso, no por rol

**Decisión:** Las Policies usan `$usuario->can('edit:cotizaciones')` en vez de `$usuario->hasRole('admin')`. El dueño del recurso siempre tiene acceso.

**Motivo:** Control granular. Un `personal` puede tener `edit:cotizaciones` sin ser admin. Se puede crear un rol `supervisor` con permisos mixtos.

**Implementado:** 20-may-2026 (Fase 1, refinado en Fase 7).

---

## 🧩 Guía para Agregar un Nuevo Evento

1. **Crear la clase del evento** en `app/Events/` — extender `Illuminate\Foundation\Events\Dispatchable`
2. **Crear el listener** en `app/Listeners/` — implementar `ShouldQueue` si es asíncrono
3. **Registrar el mapeo** en `EventServiceProvider::$listen`
4. **Disparar el evento desde un Observer** (nunca desde un servicio ni desde `$dispatchesEvents`)
5. **Agregar test** en `tests/Feature/EventSystemTest.php` verificando que se dispara exactamente 1 vez

```php
// EventServiceProvider.php
protected $listen = [
    NuevoEvento::class => [
        NuevoListener::class,
    ],
];
```

---

## 📂 Estructura de Directorios Clave

```
app/
├── DTOs/                  # Value objects (CambioEstado)
├── Events/                # 8 eventos de dominio
├── Listeners/             # 9 listeners (todos en EventServiceProvider)
├── Observers/             # 7 observers (única fuente de emisión de eventos)
├── Policies/              # 4 policies (Atencion, Cotizacion, OrdenCompra, Pago)
├── Services/              # EstadoFaseService (puro, retorna DTOs)
└── Providers/
    ├── AppServiceProvider.php      # Observers + Audit/Logro hooks
    └── EventServiceProvider.php    # Mapeo Event => [Listeners]
```

---

## 🔒 Seguridad

| Capa | Mecanismo |
|------|-----------|
| Autenticación | Sanctum tokens (8h expiración), rate limiting 10/min en auth |
| Autorización | Policies por permiso (`can('edit:cotizaciones')`) + dueño |
| Sesiones | `SESSION_ENCRYPT=true` |
| Debug | `APP_DEBUG=false` en producción |
| Colas | `after_commit=true` (jobs solo tras commit de BD) |

---

## ⚡ Rendimiento

| Técnica | Impacto |
|---------|---------|
| Catálogos cacheados (Redis, 24h TTL) | ~12-18 queries eliminadas por request |
| 9 índices compuestos | Queries de sincronización 5-10x más rápidas |
| Métricas con SQL agregadas (`LAG`, `TIMESTAMPDIFF`) | Sin riesgo OOM con 100k+ registros |
| `LogroPersonalLogger` solo en 3 modelos | Miles de llamadas innecesarias eliminadas |
| `app:prune-logs --days=90` diario | Tablas de auditoría no crecen indefinidamente |
