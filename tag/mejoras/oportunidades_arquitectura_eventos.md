# Oportunidades de Mejora: Arquitectura Orientada a Eventos

Este documento detalla las oportunidades identificadas en el esquema de la base de datos para transicionar procesos que actualmente podrían tener un enfoque clásico (CRUD) hacia un **enfoque basado en tareas y eventos (Event-Driven Architecture)**, reflejando el flujo real del negocio.

---

## Oportunidad A: El Flujo Comercial y CRM (Atenciones a Cotizaciones)

**Tablas involucradas:** `atenciones`, `etapas_comerciales`, `clientes`, `atencion_historial`, `cotizaciones`.

* **El problema del enfoque CRUD:** Actualizar la etapa de una atención comercial normalmente implicaría solo cambiar un ID en la base de datos mediante un controlador, perdiendo la oportunidad de automatizar tareas secundarias.
* **La Visión Orientada a Eventos:**
  Implementar un evento `AtencionAvanzoDeEtapa` que se dispare cuando un usuario mueva una atención en el embudo comercial.
  * **Listener (`RegistrarHistorialAtencionListener`):** Escucha el evento y registra automáticamente en la tabla `atencion_historial` el movimiento, calculando cuánto tiempo pasó el cliente en la etapa anterior.
  * **Listener (`PromoverLeadAClienteListener`):** Si la nueva etapa se define como "Ganada", el sistema verifica si el registro es solo un lead y lo promueve automáticamente a la tabla de `clientes` activos.

---

## Oportunidad B: Conciliación Financiera Automática (Pagos a Cuentas por Pagar)

**Tablas involucradas:** `pagos`, `pagos_a_proveedores`, `cuentas_por_pagar`, `estados_financieros`.

* **El problema del enfoque CRUD:** Insertar un pago es una acción aislada. Conciliar la deuda y afectar la caja chica o cuenta bancaria queda como responsabilidad de un proceso manual adicional por parte del usuario.
* **La Visión Orientada a Eventos:**
  Al confirmar y registrar un pago en el sistema, se dispara el evento `PagoEmitido` (o `PagoAProveedorRegistrado`).
  * **Listener (`AmortizarCuentaPorPagarListener`):** Busca las `cuentas_por_pagar` vinculadas a ese pago y deduce automáticamente el monto abonado. Si el saldo llega a cero, cambia el estado de la cuenta a "Pagada".
  * **Listener (`ActualizarEstadoFinancieroListener`):** Afecta de forma segura el balance en la tabla `estados_financieros` de la empresa u origen de fondos asociado, registrando la salida del dinero.

---

## Oportunidad C: Sistema de Metas e Incentivos (RRHH y Ventas)

**Tablas involucradas:** `metas`, `metas_personal`, `logros_personal`, `personal`.

* **El problema del enfoque CRUD:** La carga de logros (ventas, atenciones cerradas) para los empleados depende de que un gerente o el departamento de Recursos Humanos haga revisiones periódicas y cargue los datos a mano.
* **La Visión Orientada a Eventos:**
  Aprovechar los eventos que ya ocurren en el área comercial (ej. `OrdenCompraAprobada`, `AtencionGanada`, `CotizacionFacturada`).
  * **Listener (`ActualizarLogroVendedorListener`):** Cuando ocurre una venta o hito importante, este listener identifica al empleado responsable. Revisa en `metas_personal` si tiene metas activas relacionadas con ese hito y automáticamente calcula el progreso dinámicamente.

---

## Fase 2: Proactividad y Análisis Profundo (Próximas Mejoras)

### Oportunidad D: Notificaciones Proactivas y Gamificación
* **El Problema:** El sistema es silencioso. Los jefes y empleados tienen que entrar a revisar si algo pasó o si cumplieron una meta.
* **La Visión:** 
  * **Listener (`NotificarHitoAlcanzadoListener`):** Cuando un vendedor alcance el 50%, 80% o 100% de su meta, disparar una notificación (Correo, Slack o Reverb) para celebrar el logro.
  * **Alertas de Estancamiento:** Si una Cotización pasa más de 48 horas en un estatus crítico, disparar un evento de "Alerta" para que el gerente intervenga.

### Oportunidad E: Análisis de Cuellos de Botella (Efficiency Audit)
* **El Problema:** Sabemos qué se vendió, pero no sabemos dónde perdemos tiempo en el proceso comercial.
* **La Visión:**
  * Aprovechar la columna `tiempo_transcurrido_segundos` de la tabla `logros_personal` para crear un Dashboard de eficiencia.
  * Identificar qué etapas del CRM tardan más (ej: "Las cotizaciones pasan 5 días en promedio esperando aprobación del cliente").
  * Comparar la velocidad de cierre entre diferentes vendedores para identificar mejores prácticas.
