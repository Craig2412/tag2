# CHANGELOG — TAG2

## [1.0.0] — 2026-05-20

### 🏗️ Arquitectura
- **EventServiceProvider** centralizado — única fuente de verdad Evento → Listeners
- **DTO `CambioEstado`** — servicios retornan value objects, no disparan eventos
- **`AtencionStateService` + `OrdenStateService`** — `EstadoFaseService` dividido en 2 servicios especializados + fachada de compatibilidad
- **0 `saveQuietly`/`updateQuietly`** en todo el proyecto — listeners idempotentes
- **Policies por permiso** — `can('edit:cotizaciones')` en vez de `hasRole('admin')`, 4 policies
- **`LogroPersonalLogger`** — solo en Atencion, Cotizacion, OrdenCompra (ya no wildcard `eloquent.*`)

### 🔒 Seguridad
- `APP_DEBUG=false` en `.env` de desarrollo
- `SESSION_ENCRYPT=true`
- Rate limiting: `throttle:10,1` en auth, `throttle:60,1` en toda la API
- Sanctum tokens: expiración 8 horas (alineado con Next.js)
- `.env.example` completo con todas las claves

### 🐛 Bugs corregidos
- IDs hardcodeados `= 1` → consulta dinámica `EstadoFinanciero::where('slug', 'pendiente')`
- Campos fantasma eliminados: `referencia`, `id_usuario`, `monto_pagado`
- `MetricasController::promedioTiempoPagoOrden` — propiedad inexistente `->estatus` corregida a `->slug`
- `PagoController` — `str_replace` → `Storage::url()`
- Scribe docs: `@bodyParam estatus` → `@bodyParam id_estado_conciliacion`
- Doble emisión de `CotizacionGuardado` — unificado en Observer
- Doble llamada a `sincronizarEstadoFinanciero` en `PagoOrdenCompra` — unificado en evento
- `ClienteService::assignRole('cliente')` — verificado, el rol existe en seeders
- Borrado duplicado `PagoProveedor` — unificado en Observer

### 🧹 Limpieza
- `RegistrarHistorialCotizacionListener` (dead code, `handle()` vacío)
- `PagoProveedorCreado` (evento huérfano)
- `PagoProveedorEliminado` (evento huérfano)
- `$dispatchesEvents` eliminado de `Cotizacion` y `PagoOrdenCompra`

### ⚡ Rendimiento
- 9 índices compuestos en tablas de alto tráfico
- Catálogos cacheados con `Cache::remember(key, 86400)` — ~12 queries eliminadas por request
- `MetricasController` reescrito con SQL agregadas (`LAG()`, `TIMESTAMPDIFF()`, `AVG()`)
- `app:prune-logs --days=90` — purga diaria de `audit_logs` y `logros_personal`
- `queue.php`: `after_commit=true`
- `composer dev`: `queue:work --tries=3 --backoff=5` (antes `queue:listen --tries=1`)

### 🧪 Tests
- **9 tests · 32 assertions** (antes 0 tests útiles)
- `CommercialCycleTest` — flujo feliz, soft-delete cascada, rechazo total
- `EdgeCasesTest` — transición financiera, reapertura atención, saldos CxP parciales
- `EventSystemTest` — `CotizacionGuardado` no se duplica

### 📦 DevOps
- `ecosystem.config.cjs` — PM2 para queue worker + reverb
- `.env.example` completo con DB, Redis, Mail, AWS
- `docs/arquitectura.md` — 171 líneas con diagrama de flujo, 5 ADR, guía para nuevos eventos

---

## [0.1.0] — 2026-05-18 (pre-refactorización)

- Sistema base: Laravel 12, PHP 8.2, MySQL, Sanctum, Spatie Permissions, Reverb
- Ciclo comercial: Atención → Cotización → Orden de Compra → Cuentas por Pagar → Pagos
- Máquina de estados financieros (ingresos + egresos independientes)
- Scribe documentación de API
- Audit Log con jobs asíncronos
- Logros de personal con temporalidades flexibles
- **Puntuación inicial: 5.1/10**
