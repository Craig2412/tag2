# Propuesta: Módulo de Cuentas por Pagar (CxP) - Arquitectura Profesional

Este documento detalla la transición del sistema actual de seguimiento de costos a un módulo robusto de **Cuentas por Pagar (CxP)**, diseñado para escalar junto con las operaciones de la empresa.

---

## 🏗️ Arquitectura Final Decidida

La propuesta se basa en separar la **operación** (Servicios) de la **obligación financiera** (Cuentas por Pagar).

### Flujo General

```
OrdenCompra aprobada
    → Event: OrdenCompraAprobada
        → Listener: GenerarCuentasPorPagarListener
            → Agrupa servicios por id_proveedor
            → Crea 1 CuentaPorPagar por (orden + proveedor)
```

**Ejemplo:** Una orden con 3 servicios de 2 proveedores distintos → genera **2 registros CxP**.

El sistema espeja simétricamente el flujo de cobros:

```
COBROS:  pagos  →  pagos_ordenes_compra      →  ordenes_compra
PAGOS:   pagos_a_proveedores  →  pago_proveedor_cuentas  →  cuentas_por_pagar
```

---

## 🗄️ Tablas

### `cuentas_por_pagar` (Nueva)
Centraliza todo lo que la empresa debe a sus proveedores.

| Campo | Tipo | Detalle |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `id_orden_compra` | bigint FK | Orden que la genera |
| `id_proveedor` | bigint FK | A quién le debemos |
| `monto_total` | decimal(12,2) | SUM(servicios.costo) del proveedor en esa orden |
| `saldo_pendiente` | decimal(12,2) | Empieza = monto_total, baja con cada pago |
| `estatus` | enum/FK | `pendiente` / `abonado` / `liquidado` / `retenido` |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |
| `deleted_at` | timestamp | Soft delete |

> **Regla de negocio:** El `monto_total` se calcula con el campo `costo` del servicio (sin IVA adicional).

---

### `pagos_a_proveedores` (Modificada - Encabezado del pago)
Registra la transacción bancaria saliente. Un pago puede cubrir múltiples CxP (pagos en lote).

| Campo | Tipo | Detalle |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `id_proveedor` | bigint FK | A quién se paga |
| `monto_total` | decimal(12,2) | Total de la transacción |
| `id_tasa_cambio` | bigint FK | Tasa del **día del pago** (distinta a la del servicio) |
| `referencia` | varchar(255) | Número de transacción bancaria (único) |
| `fecha_pago` | date | Fecha efectiva del pago |
| `id_metodo_pago` | bigint FK | FK metodos_pago |
| `comprobante` | varchar(255) | PDF/imagen (opcional) |
| `estatus` | FK | `pendiente` / `confirmado` / `anulado` |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |
| `deleted_at` | timestamp | Soft delete |

> **Nota:** `id_tasa_cambio` aquí es la tasa del momento del pago, conceptualmente distinta a `servicios.id_tasa_cambio` que es la tasa de cotización al cliente.

---

### `pago_proveedor_cuentas` (Nueva - Pivote para lotes)
Relaciona un pago con las CxP que cubre.

| Campo | Tipo | Detalle |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `id_pago_proveedor` | bigint FK | FK pagos_a_proveedores |
| `id_cuenta_por_pagar` | bigint FK | FK cuentas_por_pagar |
| `monto_asignado` | decimal(12,2) | Cuánto de este pago va a esta CxP |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |

> **Constraint:** Unique en (`id_pago_proveedor`, `id_cuenta_por_pagar`) para evitar duplicados.

---

## ⚙️ Mecanismo de Automatización

### ¿Por qué Event + Listener y no Observer?

| | Observer | Event + Listener ✅ |
|---|---|---|
| Disparo | Automático en TODO `updated` del modelo | Explícito, solo al aprobar la orden |
| Claridad | Puede dispararse en cambios no deseados | Solo cuando el negocio lo indica |
| Testabilidad | Difícil de aislar | Fácil de testear por separado |
| Extensibilidad | Limitado | Se pueden agregar más listeners (emails, notificaciones) |

### Clases a crear

- **Event:** `App\Events\OrdenCompraAprobada`
- **Listener:** `App\Listeners\GenerarCuentasPorPagarListener`
- **Service:** `App\Services\CuentasPorPagarService`

---

## 💳 Lógica de Pago en Lote

1. El usuario selecciona N CxP pendientes del mismo proveedor
2. El sistema crea **1 registro** en `pagos_a_proveedores`
3. Por cada CxP seleccionada → insert en `pago_proveedor_cuentas` con su `monto_asignado`
4. Se recalcula `saldo_pendiente` en cada CxP
5. Si `saldo_pendiente = 0` → CxP pasa a `liquidado`
6. Si **todas** las CxP de la orden están liquidadas → `ordenes_compra.id_estado_financiero` se actualiza automáticamente

---

## 🚀 Posibilidades y Beneficios

### 1. Automatización Contable
Al aprobar una Orden de Compra, el sistema generará automáticamente los registros de CxP. **Nadie carga la deuda manualmente**, el sistema ya sabe qué se debe y a quién.

### 2. Pagos en Lote por Proveedor
Un solo comprobante bancario puede liquidar múltiples órdenes del mismo proveedor, registrando la distribución exacta del monto.

### 3. Gestión de Tesorería (Flujo de Caja)
- **Deuda por Proveedor:** *"Debemos $50,000 en total a la Aerolínea X"*
- **Proyección de Salidas:** Cuánto dinero se necesita para liquidar todas las órdenes activas

### 4. Seguridad y Auditoría
- Validación de referencias bancarias duplicadas
- Control de saldo: no se puede pagar más del monto pactado en la CxP
- Cierre automático cuando el saldo llega a $0

---

## ✅ Decisiones Tomadas

| Pregunta | Decisión |
|---|---|
| ¿El monto al proveedor incluye IVA? | No, solo el campo `costo` del servicio |
| ¿Se permiten pagos en lote? | Sí, mediante la tabla pivote `pago_proveedor_cuentas` |
| ¿Mecanismo de automatización? | Event + Listener (`OrdenCompraAprobada`) |
| ¿`id_tasa_cambio` en el pago? | Sí, tasa del día del pago (distinta a la del servicio) |
| ¿`id_proveedor` directo en CxP? | Sí, para facilitar consultas sin JOIN a servicios |

---

## 📋 Plan de Implementación (Fase 2)

1. **Migraciones:** `cuentas_por_pagar`, modificar `pagos_a_proveedores`, `pago_proveedor_cuentas`
2. **Modelos:** `CuentaPorPagar`, `PagoAProveedor`, `PagoProveedorCuenta`
3. **Event + Listener:** `OrdenCompraAprobada` + `GenerarCuentasPorPagarListener`
4. **Service:** `CuentasPorPagarService` (lógica de pago en lote y recálculo de saldo)
5. **Controllers + Routes:** CRUD de CxP y registro de pagos
