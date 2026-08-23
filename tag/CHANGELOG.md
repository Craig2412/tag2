# CHANGELOG — TAG2

## [1.1.0] — 2026-08-21

### 👥 Usuarios y departamentos
- Migración `2026_08_20_000001_add_departamento_to_personal_table` — columnas `departamento` y `cargo` en `personal`
- Seeder `UsuariosDepartamentosSeeder` — 19 usuarios en 4 departamentos (correo `nombre@tag.com`, clave `123456789`), mapeo de privilegios a roles (`admin`/`personal`), ignorando `EMISOR RETENCIONES`

### 🧾 Facturación fiscal (IVA + retenciones)
- Tabla `conceptos_fiscales` — impuestos/retenciones configurables (a quién, sobre qué base, % editable, exclusión por palabra clave)
- Seeder `ConceptosFiscalesSeeder` — 10 conceptos (6 cliente + 4 empresa)
- `FacturaService` — cálculo por servicio (base, exento, IVA, retenciones) + totales
- Endpoints: `GET /api/ordenes-compra/{id}/factura` y `GET /api/ordenes-compra/{id}/retenciones`
- Excepción ISLR: no aplica si el servicio (por `tipoServicio.tipo_servicio`) contiene "boleto"
- `TipoServicioSeeder`: "Vuelo" → "Boleto"

### 🧾 Persistencia de facturas (emitir)
- Tablas `facturas`, `factura_detalles`, `factura_retenciones` (migración `2026_08_21_000003`)
- Modelos `Factura`, `FacturaDetalle`, `FacturaRetencion`
- `FacturaService::emitir()` — persiste la factura (cabecera + detalles + retenciones congeladas), idempotente y transaccional
- Emisión automática en `GenerarOrdenDesdeCotizacionListener` al crear la OC
- Numeración secuencial `A-00000001` (serie + correlativo por año)
- Endpoints: `POST /api/ordenes-compra/{id}/factura/emitir`, `GET /api/facturas`, `GET /api/facturas/{ordenCompra}`
- Datos fiscales del emisor (RIF/razón social) desde tabla `empresas`

### 🏨 Proveedores (aliados)
- Migración `2026_08_21_000001_update_proveedores_table` — `ciudad`, `cargo_contacto`, `caracteristica`, `comision_tag`; quita `unique` de `correo_empresa`
- Migración `2026_08_21_000002_make_comision_tag_numeric` — `comision_tag` → `decimal(8,4)`
- Seeder `ProveedoresAliadosSeeder` — 307 aliados (embebidos), mapeo de `Producto` a 6 `TipoProveedor` + pivote `TipoServicio`, comisión numérica (neta/convenio/vacío → 0.0), RIF placeholder único, correo placeholder para faltantes

### 📚 Documentación
- `docs/SESION_2026-08-21_FACTURACION_PROVEEDORES.md` — documentación completa de la sesión

---

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
