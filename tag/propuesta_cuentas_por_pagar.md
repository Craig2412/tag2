# Propuesta: Módulo de Cuentas por Pagar (CxP) - Arquitectura Profesional

Este documento detalla la transición del sistema actual de seguimiento de costos a un módulo robusto de **Cuentas por Pagar (CxP)**, diseñado para escalar junto con las operaciones de la empresa.

## 🏗️ Nueva Arquitectura (Panorama B)

La propuesta se basa en separar la **operación** (Servicios) de la **obligación financiera** (Cuentas por Pagar).

### 1. Tablas y Modelos

#### `cuentas_por_pagar` (Nueva)
Centraliza todo lo que la empresa debe a sus proveedores.
- **Campos**:
  - `id_orden_compra`: Vínculo con la orden aprobada.
  - `id_proveedor`: A quién le debemos.
  - `monto_total`: Suma de los costos de los servicios en esa OC para ese proveedor.
  - `saldo_pendiente`: Monto que falta liquidar.
  - `estatus`: `pendiente`, `abonado`, `liquidado`, `retenido`.

#### `pagos_a_proveedores` (Modificada)
Registra los movimientos de dinero saliente.
- **Campos**:
  - `id_cuenta_por_pagar`: Vínculo con la obligación.
  - `monto`: Cuánto se pagó.
  - `referencia`: Código de transacción (obligatorio).
  - `id_metodo_pago`: Cómo se pagó.
  - `comprobante`: (Opcional) Link al archivo PDF/Imagen.

---

## 🚀 Posibilidades y Beneficios

### 1. Automatización Contable
Al aprobar una **Orden de Compra**, el sistema generará automáticamente los registros de CxP. **Nadie tiene que cargar la deuda manualmente**, el sistema ya sabe qué se debe y a quién.

### 2. Gestión de Tesorería (Flujo de Caja)
Puedes obtener reportes instantáneos de:
- **Deuda por Proveedor**: *"Debemos $50,000 en total a la Aerolínea X"*.
- **Vencimientos**: Filtrar deudas de órdenes de compra antiguas.
- **Proyección de Salidas**: Saber cuánto dinero necesitas para liquidar todas las órdenes de la semana.

### 3. Seguridad y Auditoría
- **Validación de Referencias**: El sistema impedirá que se use la misma referencia bancaria dos veces, evitando pagos duplicados.
- **Control de Saldo**: No se puede pagar más del monto pactado en la CxP.
- **Cierre Operativo**: Cuando la CxP llega a saldo $0$, los servicios asociados pueden marcarse automáticamente como "Pagados al 100%".

---

## 🛠️ Próximo Paso (Fase 1)

Antes de implementar este módulo de CxP, realizaremos el **Refactor de Servicios y Cotizaciones** para pasar de Muchos-a-Muchos a **Uno-a-Muchos**, lo cual es el cimiento necesario para que esta arquitectura financiera funcione correctamente. *(Completado)*

## 📝 Nuevos Puntos por Decidir (Fase 2)

### 1. Pagos en Lote por Proveedor
- **Pregunta**: ¿Se permitirá pagar múltiples servicios de un mismo proveedor en una sola transacción/referencia bancaria?
- **Implicación**: Requiere que la interfaz de CxP permita agrupar deudas pendientes por `id_proveedor`.

### 2. Origen del Cálculo para el Proveedor
- **Pregunta**: ¿El monto a pagar al proveedor es el `costo` plano registrado en el servicio, o se debe calcular algún IVA adicional sobre ese costo?
- **Estado Actual**: Actualmente el sistema suma `total_servicio` para la Orden de Compra, pero el `total_servicio` parece estar más ligado al precio de venta. Es vital definir si existe un `total_costo` (Costo + IVA Proveedor).
