# 🔍 Gaps Detectados — TAG2

> Análisis del sistema — 18 de mayo de 2026

---

## 🔴 Alta Prioridad (impactan operación diaria)

### 📧 Notificaciones
- **Estado**: ❌ No implementado
- **Descripción**: El sistema no envía ninguna notificación por email ni push.
- **Impacto**: Los usuarios deben entrar manualmente al sistema para ver cambios.
- **¿Cuándo debería notificar?**:
  - Cotización aprobada / rechazada
  - Pago registrado (cliente o proveedor)
  - Atención cambia de estado o etapa
  - Cotización próxima a vencer
  - Orden de Compra completada
- **Solución propuesta**: Laravel Notifications + Mail (ya existe `correo` en `Usuario`).

### ⏰ Vencimientos automáticos
- **Estado**: ❌ No implementado
- **Descripción**: Las cotizaciones tienen `fecha_vencimiento` y el accessor `esta_vencida`, pero ninguna tarea programada las marca como `rechazada` automáticamente ni alerta a los usuarios.
- **Impacto**: Cotizaciones quedan en estado `pendiente` para siempre aunque estén vencidas.
- **Solución propuesta**: Scheduled Job (`php artisan schedule:work`) que cada hora marque como `rechazada` las cotizaciones vencidas sin OC asociada.

### 📄 Numeración secuencial
- **Estado**: ❌ No implementado
- **Descripción**: Cotizaciones y Órdenes de Compra no tienen numeración fiscal/secuencial (N° 001-00001).
- **Impacto**: Imposible facturar formalmente sin numeración de documentos.
- **Solución propuesta**: Agregar campo `numero_documento` con secuencia por tipo/año.

### 🧾 Facturación
- **Estado**: ❌ No implementado
- **Descripción**: No existe módulo de facturas. La Orden de Compra es el documento final del flujo pero no constituye una factura fiscal.
- **Faltante**: Número de factura, timbrado, PDF, envío al cliente.
- **Solución propuesta**: Módulo `Factura` asociado a `OrdenCompra`, con generación de PDF y envío por correo.

---

## 🟡 Media Prioridad (mejoran el negocio)

### 💰 Cálculo de comisiones
- **Estado**: 🟡 Dato guardado, lógica ausente
- **Descripción**: El modelo `Personal` tiene el campo `porcentaje_comision` pero **nunca se usa** en ninguna lógica de negocio.
- **Impacto**: Las comisiones deben calcularse manualmente fuera del sistema.
- **Solución propuesta**: Job/Listener que al marcarse una OC como `pagado`, calcule la comisión del personal asignado a la atención y la registre en una tabla `comisiones`.

### 📎 Adjuntos / Upload de archivos
- **Estado**: 🟡 Parcial
- **Descripción**: El campo `comprobante` en `PagoProveedor` es un string (probablemente path/URL) pero no hay manejo de upload de archivos en el sistema.
- **Impacto**: No se pueden adjuntar comprobantes de pago, cotizaciones en PDF, ni documentos.
- **Solución propuesta**: Usar Laravel Filesystem + `Storage` para upload de comprobantes en `PagoProveedor` y `Pago`.

### 📊 Dashboard / Pipeline
- **Estado**: 🟡 Básico
- **Descripción**: Solo existen endpoints básicos de métricas (`metricas/personal`, `metricas/generales`).
- **Faltante**:
  - Pipeline de ventas (embudo por etapa comercial)
  - Aging de cuentas por cobrar/pagar
  - Proyección de ingresos mensuales
  - Ranking de personal por ventas
  - Tasa de conversión (cotización → OC)
- **Solución propuesta**: Expandir `MetricasController` con queries agregadas y endpoints adicionales.

### 🔄 Workflow de aprobación
- **Estado**: ❌ No implementado
- **Descripción**: Cualquier usuario autenticado puede aprobar una cotización. No hay límites por monto ni requerimiento de aprobación por gerente.
- **Impacto**: Riesgo de aprobaciones no autorizadas en operaciones grandes.
- **Solución propuesta**: Middleware/policy que requiera rol `admin` para aprobar cotizaciones sobre cierto monto configurable.

---

## 🟢 Baja Prioridad (nice to have)

### 🏦 Conciliación bancaria
- **Estado**: 🟡 Solo catálogo
- **Descripción**: Existe el modelo `EstadoConciliacion` pero no hay proceso de conciliación (importar extracto bancario, matching automático con pagos registrados).
- **Solución propuesta**: Módulo de importación CSV de extractos + algoritmo de matching por monto/fecha/referencia.

### 📝 Notas de crédito / débito
- **Estado**: ❌ No implementado
- **Descripción**: No hay forma de ajustar montos después de emitida una Orden de Compra.
- **Impacto**: Devoluciones, descuentos post-emisión o ajustes requieren intervención manual en BD.
- **Solución propuesta**: Modelo `NotaCredito` / `NotaDebito` asociado a `OrdenCompra`.

### 🧮 Retenciones (ISLR / IVA)
- **Estado**: ❌ No implementado
- **Descripción**: No se registran retenciones de ISLR ni IVA en los pagos de clientes.
- **Nota**: La base de datos ya tiene `TipoContribuyente.porcentaje_iva` y los servicios tienen `iva_establecido`. Solo falta el módulo de facturación que ensamble los datos y calcule las retenciones.
- **Solución propuesta**: Agregar campos `retencion_islr` y `retencion_iva` en `Pago`. Implementar al mismo tiempo que facturación.

### 📋 Templates PDF
- **Estado**: ❌ No implementado
- **Descripción**: No hay generación de PDFs para cotizaciones, órdenes de compra ni facturas.
- **Solución propuesta**: Usar `barryvdh/laravel-dompdf` o `laravel-snappy` con templates Blade.

### 🏷️ Catálogo de productos/servicios enriquecido
- **Estado**: 🟡 Básico
- **Descripción**: `TipoServicio` solo tiene `tipo_servicio` e `iva_defecto`.
- **Faltante**: Código SKU, precios base, unidades de medida, descripción larga.
- **Solución propuesta**: Expandir `TipoServicio` con campos adicionales.

---

## ✅ Lo que ya funciona bien

| Funcionalidad | Estado |
|--------------|--------|
| Atenciones (tickets/oportunidades) | ✅ Completo |
| Cotizaciones con servicios | ✅ Completo |
| Órdenes de Compra automáticas | ✅ Completo |
| Cuentas por Pagar (CxP) | ✅ Completo |
| Pagos de clientes (ingresos) | ✅ Completo |
| Pagos a proveedores (egresos) | ✅ Completo |
| Estados financieros automáticos | ✅ Completo |
| Historial de cambios (audit + historial) | ✅ Completo |
| Logros de personal | ✅ Completo |
| Metas con temporalidad | ✅ Completo |
| Roles y permisos (Spatie) | ✅ Completo |
| Soft Deletes en cascada (observers) | ✅ Completo |
| Eventos / Listeners / Observers | ✅ Completo |
| API REST documentada (Scribe) | ✅ Completo |
| Broadcasting (Reverb) | ✅ Completo |
| Diagnóstico y pruebas automatizadas | ✅ Completo |

---

## 🎯 Orden sugerido de implementación

1. ⏰ **Vencimientos automáticos** — máximo valor, mínimo esfuerzo
2. 📧 **Notificaciones** — email en cambios clave
3. 📊 **Dashboard / Pipeline** — visibilidad del negocio
4. 📄 **Numeración secuencial** — prepara para facturación
5. 💰 **Cálculo de comisiones** — cierra el ciclo de ventas
6. 📎 **Upload de archivos** — comprobantes y documentos
7. 🔄 **Workflow de aprobación** — control por montos
8. 🧾 **Facturación** — requiere items 1-7 como base
