# 🔍 INFORME DE ANÁLISIS EXHAUSTIVO — Sistema TAG2

> **Fecha:** 18 de mayo de 2026  
> **Stack:** Laravel 12 · PHP 8.2 · MySQL · Sanctum · Spatie Permissions · Reverb  
> **Alcance:** Código completo — modelos, controladores, servicios, observers, listeners, jobs, migraciones, rutas, configuración  
> **Hallazgos totales:** 53

---

## 📊 Puntuación por Dimensión

| Dimensión | Nota | 
|-----------|------|
| Arquitectura y diseño | 7/10 |
| Funcionalidad core | 8/10 |
| Seguridad | 4/10 |
| Mantenibilidad | 5/10 |
| Rendimiento | 6/10 |
| Escalabilidad | 4/10 |
| Pruebas | 1/10 |
| Completitud funcional | 6/10 |
| Estándares y buenas prácticas | 5/10 |
| **Nota global** | **5.1 / 10** |

> *"Un buen prototipo avanzado con una base arquitectónica sólida, que necesita una iteración de hardening antes de exponerse a usuarios reales."*

---

# 🔴 SEGURIDAD (9 hallazgos)

### S1 · Credenciales de BD en texto plano en `.env`
- **Archivo:** `.env:17-18`
- **Impacto:** 🔴 Crítico
- **Problema:** `DB_USERNAME=root` y `DB_PASSWORD=10379275` hardcodeados. Si el archivo `.env` se filtra (error de servidor, backup, commit accidental), la base de datos queda expuesta.
- **Acción:** Usar variables de entorno del sistema o un vault en producción. Rotar contraseña.

### S2 · `APP_DEBUG=true` explícito en `.env`
- **Archivo:** `.env:4`
- **Impacto:** 🔴 Crítico
- **Problema:** Si se despliega así en producción, expone stack traces completos con rutas, queries SQL, variables de entorno y datos sensibles en cada error.
- **Acción:** En producción debe ser `false`. Mejor aún, no definirlo en `.env` y solo activarlo en `.env.local`.

### S3 · `SESSION_ENCRYPT=false`
- **Archivo:** `.env:26`
- **Impacto:** 🔴 Crítico
- **Problema:** Las cookies de sesión viajan sin cifrar.
- **Acción:** Cambiar a `true`.

### S4 · Sin rate limiting en endpoints de autenticación
- **Archivo:** `routes/api.php:34-38`
- **Impacto:** 🔴 Crítico
- **Problema:** `/login`, `/login/admin`, `/login/user`, `/register` no tienen middleware `throttle`. Ataques de fuerza bruta ilimitados.
- **Acción:** Agregar `->middleware('throttle:10,1')` en cada ruta de auth.

### S5 · Sanctum: tokens sin expiración
- **Archivo:** `config/sanctum.php:55`
- **Impacto:** 🔴 Crítico
- **Problema:** `'expiration' => null`. Un token robado da acceso perpetuo.
- **Acción:** Configurar `'expiration' => 1440` (24 horas) o similar.

### S6 · `INTERNAL_CONTRACT_SECRET` en texto plano
- **Archivo:** `.env:55`
- **Impacto:** 🟠 Alto
- **Problema:** Secreto de endpoint de contrato expuesto en el mismo archivo.
- **Acción:** Rotar y mover a variable de entorno del sistema.

### S7 · Sin autorización granular en actualización de Órdenes de Compra
- **Archivo:** `app/Http/Controllers/OrdenCompraController.php:52`
- **Impacto:** 🟠 Alto
- **Problema:** `update()` permite cambiar `id_estado_orden_compra` sin validar rol ni límite de monto. Cualquier usuario autenticado puede aprobar o completar una OC.
- **Acción:** Implementar Policy `OrdenCompraPolicy` con validación por monto y rol.

### S8 · Sin autorización granular en actualización de Cotizaciones
- **Archivo:** `app/Http/Controllers/CotizacionController.php:157`
- **Impacto:** 🟠 Alto
- **Problema:** Mismo patrón que S7. Cualquier autenticado puede aprobar una cotización y disparar la generación de OC.
- **Acción:** Implementar Policy `CotizacionPolicy`.

### S9 · Credenciales de Reverb en `.env`
- **Archivo:** `.env:57-59`
- **Impacto:** 🟡 Medio
- **Problema:** `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` visibles.
- **Acción:** En producción, fuera del `.env`.

---

# 🪲 BUGS (15 hallazgos)

### B1 · Doble emisión de eventos en `Cotizacion` — riesgo de ciclos
- **Archivos:** `app/Models/Cotizacion.php:32` + `app/Observers/CotizacionObserver.php:14`
- **Impacto:** 🔴 Crítico
- **Problema:** `CotizacionGuardado` se dispara DOS VECES por cada `save()`: una por `$dispatchesEvents` del modelo y otra por el Observer `saved()`. Cada listener se ejecuta duplicado.
- **Acción:** Eliminar `$dispatchesEvents` del modelo y dejar solo el Observer.

### B2 · Doble emisión de eventos en `PagoOrdenCompra`
- **Archivos:** `app/Models/PagoOrdenCompra.php:20` + `app/Observers/PagoOrdenCompraObserver.php`
- **Impacto:** 🔴 Crítico
- **Problema:** `PagoOrdenCompraGuardado` se dispara dos veces. `SincronizarEstadoFinancieroListener` corre duplicado.
- **Acción:** Eliminar `$dispatchesEvents` del modelo y dejar solo el Observer.

### B3 · IDs de estado financiero hardcodeados (`= 1`)
- **Archivo:** `app/Listeners/GenerarOrdenDesdeCotizacionListener.php:48-49`
- **Impacto:** 🟠 Alto
- **Problema:** `'id_estado_financiero' => 1` y `'id_estado_financiero_egreso' => 1` asumen que el ID 1 es "pendiente". Si el catálogo cambia de orden, asigna un estado incorrecto.
- **Acción:** Consultar `EstadoFinanciero::where('slug', 'pendiente')->firstOrFail()->id` como ya se hace en otros listeners.

### B4 · Rol `cliente` inexistente en `ClienteService`
- **Archivo:** `app/Services/ClienteService.php:23`
- **Impacto:** 🟠 Alto
- **Problema:** `$usuario->assignRole('cliente')` revienta porque el rol `cliente` no existe en ninguna migración/seeder. Los roles reales son `admin`, `user`, `personal`.
- **Acción:** Cambiar a `assignRole('user')`.

### B5 · Campo `referencia` fantasma en comando de prueba
- **Archivo:** `app/Console/Commands/TestMasterCommercialCycle.php:47`
- **Impacto:** 🟠 Alto
- **Problema:** Asigna `'referencia' => 'MASTER-' . time()` a `Cotizacion::create()` pero el modelo no tiene ese campo en `$fillable` ni en la migración.
- **Acción:** Eliminar la línea o agregar el campo.

### B6 · Campo `id_usuario` fantasma en comando de prueba
- **Archivo:** `app/Console/Commands/TestMasterCommercialCycle.php:94`
- **Impacto:** 🟠 Alto
- **Problema:** Asigna `'id_usuario' => 1` a `PagoProveedor::create()` pero el modelo no tiene el campo en `$fillable` ni en la migración.
- **Acción:** Eliminar la línea.

### B7 · Doble lógica de borrado contradictoria en `PagoProveedor`
- **Archivos:** `app/Http/Controllers/PagoProveedorController.php:157` + `app/Observers/PagoProveedorObserver.php:15`
- **Impacto:** 🟠 Alto
- **Problema:** El controller hace `detach()` de pivotes antes del `delete()`. El Observer en `deleting()` también borra pivotes —pero con lógica de reversión de saldos que el controller no ejecuta—. Resultado: saldos inconsistentes.
- **Acción:** Unificar en el Observer. El controller solo llama a `delete()`.

### B8 · `MetricasController` referencia propiedad inexistente
- **Archivo:** `app/Http/Controllers/MetricasController.php:122`
- **Impacto:** 🟡 Medio
- **Problema:** `$row->estatusNuevoObj->estatus ?? '' === 'pagado'` — el modelo `OrdenCompraHistorial` no tiene relación `estatusNuevoObj` con propiedad `estatus`. Este código probablemente nunca devuelve resultados correctos.
- **Acción:** Corregir la relación o usar directamente el ID del estado financiero "pagado".

### B9 · `recalcularMontoTotal()` puede causar loops de eventos
- **Archivo:** `app/Models/OrdenCompra.php:72`
- **Impacto:** 🟡 Medio
- **Problema:** `$this->forceFill([...])->save()` dentro de `recalcularMontoTotal()` dispara eventos `saved`, que ejecutan `SincronizarPadreOrdenCompraListener`, que llama a `EstadoFaseService`, que podría volver a llamar `recalcularMontoTotal()`.
- **Acción:** Usar `saveQuietly()` o mover la llamada fuera de cadenas de eventos.

### B10 · `PagoProveedorController::update()` no actualiza saldos de cuentas
- **Archivo:** `app/Http/Controllers/PagoProveedorController.php:125`
- **Impacto:** 🟡 Medio
- **Problema:** Al actualizar `monto_total` de un pago a proveedor, los saldos de `CuentaPorPagar` no se recalculan.
- **Acción:** Agregar lógica de sincronización de saldos en el update.

### B11 · `ServicioObserver::saving()` recalcula `total_servicio` innecesariamente
- **Archivo:** `app/Observers/ServicioObserver.php:16`
- **Impacto:** 🟢 Bajo
- **Problema:** En cada `saving`, recalcula `total_servicio` incluso si solo se actualizó `descripcion`. Es idempotente pero añade overhead.
- **Acción:** Verificar si los campos de monto cambiaron antes de recalcular.

### B12 · `PagoController` — `str_replace` frágil para rutas de archivos
- **Archivo:** `app/Http/Controllers/PagoController.php:96`
- **Impacto:** 🟢 Bajo
- **Problema:** `str_replace('public/', 'storage/', $rutaComprobante)` en lugar de `Storage::url()`.
- **Acción:** Usar `Storage::url($rutaComprobante)`.

### B13 · `getEmailAttribute()` como accessor no estándar
- **Archivo:** `app/Models/Usuario.php:84-87`
- **Impacto:** 🟢 Bajo
- **Problema:** `getEmailAttribute()` devuelve `$this->correo`. Paquetes que usen `$user->getAttribute('email')` fallarán.
- **Acción:** Agregar un accessor `email` en `$appends` o mapear correctamente.

### B14 · Sin soft-delete en cascada para Atencion → Cotizacion
- **Archivo:** Varios modelos
- **Impacto:** 🟢 Bajo
- **Problema:** Al eliminar una `Atencion`, sus `Cotizacion` no se eliminan. Las `OrdenCompra` de cotizaciones eliminadas quedan huérfanas.
- **Acción:** Agregar observer/listener de cascada.

### B15 · Campo `estatus` documentado pero código usa `id_estado_conciliacion`
- **Archivo:** `app/Http/Controllers/PagoController.php:54` (docs Scribe)
- **Impacto:** 🟢 Bajo
- **Problema:** La documentación dice `estatus` pero la implementación real usa `id_estado_conciliacion`.
- **Acción:** Actualizar anotaciones Scribe.

---

# 🏗️ DEUDA TÉCNICA (11 hallazgos)

### D1 · Sin `EventServiceProvider` — eventos registrados en `AppServiceProvider`
- **Archivo:** `app/Providers/AppServiceProvider.php`
- **Impacto:** 🟠 Alto
- **Problema:** Todos los listeners y observers se registran manualmente en `AppServiceProvider::boot()` (~130 líneas). Laravel 12 soporta auto-discovery. Viola el principio de responsabilidad única.
- **Acción:** Crear `EventServiceProvider` con `$listen` y mover los registros. Dejar `AppServiceProvider` solo para la aplicación.

### D2 · Uso excesivo de `saveQuietly()` / `updateQuietly()` — mal diseño de eventos
- **Archivos:** `app/Services/EstadoFaseService.php:105,165,213`, `app/Observers/OrdenCompraObserver.php`
- **Impacto:** 🟠 Alto
- **Problema:** Guardar sin disparar eventos es un workaround de que los listeners no son idempotentes y pueden causar ciclos. Síntoma de acoplamiento circular en el sistema de eventos.
- **Acción:** Refactorizar listeners para ser idempotentes y eliminar dependencias circulares.

### D3 · Mezcla de español e inglés en código, nombres y comentarios
- **Archivos:** Todo el proyecto
- **Impacto:** 🟡 Medio
- **Problema:** `getEstaVencidaAttribute()` (español), `getTotalPagadoAttribute()` (mezcla), `nombre_usuario`, `correo`, `clave` junto a `remember_token`. Dificulta onboarding y viola convenciones Laravel.
- **Acción:** Decidir idioma estándar y migrar progresivamente.

### D4 · Nombres de tablas inconsistentes
- **Archivos:** Migraciones y modelos
- **Impacto:** 🟡 Medio
- **Problema:** `pagos_a_proveedores` (plural con `a_`), `pagos` (plural simple), `tipo_servicio` (singular), `tipos_cotizaciones` (plural), `personal` (singular). Varios modelos definen `$table` manualmente porque el nombre no sigue la convención.
- **Acción:** Estandarizar a plural inglés (`payments`, `providers`, `service_types`, etc.) en una migración de renombrado.

### D5 · `getRawOriginal()` usado como workaround de casts inconsistentes
- **Archivo:** `app/Http/Controllers/CotizacionController.php:173`
- **Impacto:** 🟡 Medio
- **Problema:** `$cotizacion->getRawOriginal('id_estado_cotizacion')` evita el cast del modelo. Indica que los casts no están alineados con la lógica de negocio.
- **Acción:** Revisar y alinear casts en el modelo.

### D6 · Sin documentación de arquitectura (ADR, decisiones técnicas)
- **Archivo:** `mejoras/*.md`
- **Impacto:** 🟡 Medio
- **Problema:** Los archivos en `mejoras/` son ideas de features, no documentación técnica. No hay registro de por qué se eligió este diseño de eventos, observers, o máquina de estados.
- **Acción:** Crear `docs/arquitectura.md` documentando decisiones clave.

### D7 · `config/queue.php` — `after_commit => false`
- **Archivo:** `config/queue.php:36`
- **Impacto:** 🟡 Medio
- **Problema:** Los jobs se despachan antes del commit de la transacción. Si la transacción falla, el job corre con datos fantasma.
- **Acción:** Cambiar a `true`.

### D8 · Scribe documentación potencialmente desactualizada
- **Archivo:** `config/scribe.php`
- **Impacto:** 🟢 Bajo
- **Problema:** No se ha regenerado tras cambios en validaciones y endpoints.
- **Acción:** Ejecutar `php artisan scribe:generate` tras cada cambio de API.

### D9 · `composer.json` — namespace `Tests\\` con T mayúscula
- **Archivo:** `composer.json:39`
- **Impacto:** 🟢 Bajo
- **Problema:** PSR-4 espera nombre de directorio en minúscula coincidente. Funciona pero no es canónico.
- **Acción:** Cambiar directorio a `tests/`.

### D10 · Método `carbon_method` en `Temporalidad` — frágil
- **Archivo:** `app/Models/MetaPersonal.php:52`
- **Impacto:** 🟢 Bajo
- **Problema:** `now()->$metodoInicio()` llama dinámicamente a métodos Carbon. Si `carbon_method` no es válido, error en runtime.
- **Acción:** Validar contra una whitelist de métodos Carbon permitidos.

### D11 · Formato de respuesta de error inconsistente
- **Archivo:** Múltiples controladores
- **Impacto:** 🟢 Bajo
- **Problema:** Algunos errores usan `response()->json(['message' => ...])`, otros `abort(422, ...)`. No hay envelope estándar.
- **Acción:** Definir estructura `{ success, message, errors }` en `App\Exceptions\Handler`.

---

# 📈 ESCALABILIDAD (8 hallazgos)

### E1 · `MetricasController` carga TODOS los registros en memoria
- **Archivo:** `app/Http/Controllers/MetricasController.php`
- **Impacto:** 🔴 Crítico
- **Problema:** `calcularMetricas()` hace `->get()` sin límite, cargando todos los historiales en memoria para calcular promedios en PHP. Con 10,000+ registros, consume toda la RAM y timeout.
- **Acción:** Mover los cálculos a queries SQL agregadas (`AVG`, `TIMESTAMPDIFF`) o usar `chunk()`.

### E2 · Sin índices compuestos en tablas de alto tráfico
- **Archivos:** Migraciones
- **Impacto:** 🟠 Alto
- **Problema:** Faltan índices para queries frecuentes: `cotizaciones(id_atencion, id_estado_cotizacion)`, `atenciones(id_personal, id_estado_atencion)`, `ordenes_compra(id_estado_financiero, id_estado_financiero_egreso)`, `logros_personal(id_personal, created_at)`.
- **Acción:** Agregar `$table->index([...])` en nuevas migraciones.

### E3 · Eventos y listeners síncronos bloquean el request HTTP
- **Archivos:** `AppServiceProvider.php`, listeners
- **Impacto:** 🟠 Alto
- **Problema:** Varios listeners no implementan `ShouldQueue`. El usuario espera a que se ejecuten múltiples queries de sincronización de estados antes de recibir respuesta.
- **Acción:** Todos los listeners de sincronización deben implementar `ShouldQueue`.

### E4 · `CACHE_STORE=database` — pésimo para escalar
- **Archivo:** `.env:34`
- **Impacto:** 🟡 Medio
- **Problema:** La caché usa la misma base de datos MySQL. Redis está configurado (`REDIS_HOST=127.0.0.1`) pero no se usa. Bajo carga, la BD se satura con lecturas de caché.
- **Acción:** Cambiar a `CACHE_STORE=redis`.

### E5 · `inRandomOrder()` en asignación de personal hace table scan
- **Archivo:** `app/Http/Controllers/AtencionController.php:163`
- **Impacto:** 🟡 Medio
- **Problema:** `ORDER BY RAND()` es de las operaciones más caras en MySQL.
- **Acción:** Seleccionar un ID aleatorio con `min`/`max` o usar cache.

### E6 · `audit_logs` y `logros_personal` crecen ilimitados sin purga
- **Archivos:** `app/Jobs/PersistirAuditLog.php`, `app/Services/LogroPersonalLogger.php`
- **Impacto:** 🟡 Medio
- **Problema:** Las tablas de auditoría y logros no tienen política de retención. Con uso real, crecerán gigabytes.
- **Acción:** Agregar `app:prune-logs --days=90` como comando programado.

### E7 · Sin uso de caché en queries de negocio repetitivas
- **Archivos:** Controladores
- **Impacto:** 🟡 Medio
- **Problema:** Catálogos (`EstadoCotizacion`, `EstadoFinanciero`, etc.) se consultan una y otra vez. Redis está listo pero no se usa.
- **Acción:** Cachear catálogos con `Cache::rememberForever()` e invalidar en seeders/actualizaciones.

### E8 · Sin worker de colas corriendo para jobs asíncronos
- **Archivo:** `config/queue.php`
- **Impacto:** 🟡 Medio
- **Problema:** Los jobs `ShouldQueue` (audit logs, listeners) requieren `php artisan queue:work` corriendo. Sin él, todo se ejecuta síncrono.
- **Acción:** Documentar y configurar Supervisor/systemd para el worker.

---

# 📏 ESTÁNDARES Y BUENAS PRÁCTICAS (5 hallazgos)

### P1 · Sin Policies de autorización — solo middleware `role:admin`
- **Archivos:** `routes/api.php`, controladores
- **Impacto:** 🟠 Alto
- **Problema:** No hay control granular. Un usuario con rol `personal` puede editar cualquier recurso de cualquier otro usuario. Solo `admin` vs. `no-admin`.
- **Acción:** Crear Policies para cada modelo y registrarlas en `AuthServiceProvider`.

### P2 · Sin `EventServiceProvider` propio
- **Archivo:** `app/Providers/` — no existe
- **Impacto:** 🟡 Medio
- **Problema:** La convención Laravel es tener `EventServiceProvider` con `$listen`. Aquí todo está en `AppServiceProvider::boot()`.
- **Acción:** Crear el archivo con el mapeo `Event => Listeners`.

### P3 · Configuración `strict => true` en MySQL pero sin modo estricto en migraciones
- **Archivo:** `config/database.php:51`
- **Impacto:** 🟡 Medio
- **Problema:** Bien configurado a `true`, pero algunas migraciones usan `default(1)` que asume IDs existentes. Si no hay seeders previos, `migrate` falla.
- **Acción:** Usar valores `nullable()` en FKs que dependen de seeders.

### P4 · Sin validación de integridad referencial en Soft Deletes
- **Archivos:** Observers
- **Impacto:** 🟢 Bajo
- **Problema:** MySQL no soporta FKs con soft deletes. La integridad depende 100% de los Observers. Si un observer falla o no se registra, los datos se corrompen.
- **Acción:** Agregar tests de integración que validen cascadas de soft-delete.

### P5 · Sin `.env.example` completo — solo el `.env` real
- **Archivo:** Raíz del proyecto
- **Impacto:** 🟢 Bajo
- **Problema:** `composer.json` referencia `.env.example` en el script `setup` pero no se incluye en el repo. Nuevos desarrolladores no saben qué variables necesitan.
- **Acción:** Crear `.env.example` con todas las claves y valores dummy.

---

# 🧪 PRUEBAS (3 hallazgos)

### T1 · Cobertura de tests < 2%
- **Archivos:** `tests/Feature/`, `tests/Unit/`
- **Impacto:** 🔴 Crítico
- **Problema:** Solo 3 archivos: `ExampleTest.php` (vacío, `assertTrue(true)`), `KiuControllerTest.php`. El ciclo maestro de negocio no tiene tests automatizados.
- **Acción:** Crear test `CommercialCycleTest` que pruebe Atención → Cotización → OC → CxP → Pago.

### T2 · Sin tests de integridad de eventos
- **Archivos:** Ninguno
- **Impacto:** 🟠 Alto
- **Problema:** No se valida que la máquina de estados (pendiente → parcial → pagado) funcione correctamente ni que los listeners se ejecuten.
- **Acción:** Test que cree entidades, simule pagos y verifique estados financieros finales.

### T3 · `KiuControllerTest` potencialmente hace llamadas HTTP reales
- **Archivo:** `tests/Feature/KiuControllerTest.php`
- **Impacto:** 🟡 Medio
- **Problema:** Si las credenciales de KIU están configuradas, el test puede contactar el servicio real.
- **Acción:** Usar `Http::fake()` o mocks.

---

# 🚀 RENDIMIENTO (6 hallazgos)

### R1 · Queries N+1 en `MetricasController`
- **Archivo:** `app/Http/Controllers/MetricasController.php`
- **Impacto:** 🔴 Crítico
- **Problema:** 7+ queries separadas, varias con `->get()` sin eager loading. `$atencionHist`, `$cotizacionHist`, `$ordenHist` cargan todo en memoria.
- **Acción:** Reescribir con queries SQL agregadas (`AVG`, `COUNT`, `JOIN`).

### R2 · `AtencionController::index()` eager-loads pero duplica relaciones
- **Archivo:** `app/Http/Controllers/AtencionController.php:30`
- **Impacto:** 🟡 Medio
- **Problema:** Carga por defecto `with(['cliente', 'personal', 'origen', ...])` y luego permite `include` con los mismos. Si el include coincide, hace doble eager load.
- **Acción:** Usar `array_unique` o `loadMissing`.

### R3 · `OrdenCompraController::show()` recalcula monto total siempre
- **Archivo:** `app/Http/Controllers/OrdenCompraController.php:32`
- **Impacto:** 🟡 Medio
- **Problema:** Cada `show()` ejecuta `recalcularMontoTotal()` que hace query SUM en `servicios`.
- **Acción:** Recalcular solo cuando los servicios hayan cambiado (observer de servicios).

### R4 · `PagoProveedorController::index()` carga relaciones con `withTrashed()`
- **Archivo:** `app/Http/Controllers/PagoProveedorController.php:21`
- **Impacto:** 🟢 Bajo
- **Problema:** Carga `proveedor`, `metodoPago` siempre con `withTrashed()`, añadiendo `WHERE deleted_at IS NOT NULL OR ...` a cada query.
- **Acción:** Evaluar si realmente se necesita o solo bajo demanda.

### R5 · `CotizacionController::store()` hace `load()` masivo con closures anidados
- **Archivo:** `app/Http/Controllers/CotizacionController.php:108-115`
- **Impacto:** 🟢 Bajo
- **Problema:** El `load()` post-creación carga relaciones profundas (`atencion.cliente`, `atencion.personal`) con `withTrashed()`. Son 5 queries extra.
- **Acción:** Si el frontend no las necesita todas, reducir el eager load en POST.

### R6 · Sin compresión/optimización de assets
- **Archivo:** `vite.config.js`
- **Impacto:** 🟢 Bajo
- **Problema:** Vite está configurado pero `package.json` tiene `tailwindcss v4` y `axios`. Sin `laravel-vite-plugin` bien configurado para producción.
- **Acción:** Verificar build de producción con `vite build`.

---

# ✅ PUNTOS FUERTES DEL SISTEMA

| Fortaleza | Detalle |
|-----------|---------|
| Arquitectura de eventos/observers/listeners | Bien intencionada, con separación clara de responsabilidades |
| Máquina de estados financieros | Ingresos y egresos independientes, conceptualmente correcta |
| Soft Deletes en cascada | Implementados vía observers, bien pensados |
| API REST documentada | Scribe configurado con anotaciones |
| Spatie Permissions | Correctamente integrado con roles admin/user/personal |
| Broadcasting | Reverb configurado para tiempo real |
| Cuentas por Pagar automáticas | Generadas desde servicios de cotización sin intervención manual |
| Audit Log completo | Con job asíncrono y sanitización de datos sensibles |
| Logros de personal | Cálculo automático con temporalidades flexibles |
| Separación de capas | Services, Observers, Listeners, Jobs bien diferenciados |

---

# 🎯 PLAN DE ACCIÓN RECOMENDADO

## Fase 1: Estabilización (1-2 horas)
1. B1, B2 — Eliminar doble emisión de eventos
2. B3 — Quitar IDs hardcodeados
3. B4 — Corregir rol `cliente` fantasma
4. S4 — Agregar rate limiting a auth
5. S5 — Configurar expiración de tokens
6. B5, B6 — Limpiar campos fantasma en comandos
7. B7 — Unificar lógica de borrado de `PagoProveedor`

## Fase 2: Hardening (2-4 horas)
8. S7, S8, P1 — Implementar Policies
9. D1, P2 — Crear `EventServiceProvider`
10. E2 — Agregar índices compuestos
11. E1, R1 — Refactorizar `MetricasController`
12. B8 — Corregir query de métricas
13. E3 — Mover listeners a `ShouldQueue`

## Fase 3: Madurez (1-2 días)
14. D2 — Refactorizar diseño de eventos (eliminar `saveQuietly`)
15. T1, T2 — Crear tests del ciclo maestro
16. E4 — Migrar caché a Redis
17. E6 — Política de retención de logs
18. D3, D4 — Estandarizar idioma y nombres

---

*Informe generado tras análisis estático completo del código fuente. Se recomienda abordar la Fase 1 de inmediato antes de continuar con nuevo desarrollo funcional.*
