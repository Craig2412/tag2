# Comandos de Pruebas y Diagnóstico (Event Driven Architecture)

Durante el refactoring y desacoplamiento del sistema (migración a estados por dominio y Slugs), se crearon varios comandos de Artisan para verificar la integridad del sistema comercial sin necesidad de hacerlo manualmente en el navegador.

A continuación, la lista de comandos disponibles para pruebas:

## 1. `php artisan app:test-master`
**El "Master Cycle" — Prueba de Flujo Completo End-to-End**

Simula el ciclo comercial completo desde la prospección hasta la conciliación financiera. Útil para rellenar la base de datos con datos reales y probar el motor de estados completo.

**Lo que hace:**
1. Crea/Actualiza un Cliente.
2. Crea una Atención (Borrador -> Aprobada -> Cancelada -> Aprobada).
3. Crea Servicios para la Atención.
4. Genera una Cotización vinculada a la Atención (Borrador -> Enviada -> Aprobada).
5. Verifica que la Fase de la Atención cambió a "Cotización Aprobada".
6. Genera una Orden de Compra (Pendiente -> Pagada).
7. Verifica que la Cotización cambió su estado financiero a "Cobrada" y la Atención a "Orden Compra Generada".
8. Verifica que se generen automáticamente las Cuentas por Pagar (Egresos) basadas en los servicios tercerizados.

**Modificadores:**
- `php artisan app:test-master --no-rollback`: Ejecuta el ciclo y **guarda** los datos en la base de datos (ideal para poblar la BD con datos de prueba reales para el dashboard). Por defecto, el comando hace un rollback automático al finalizar.

---

## 2. `php artisan app:test-eventos`
**Prueba de Integración y Tracking de Metas**

Ejecuta una verificación silenciosa sobre los Observers, Listeners y el LogroPersonalLogger. Al finalizar, muestra un reporte visual con barras de progreso del cumplimiento de metas de los vendedores.

**Lo que hace:**
- Fuerza la cola a modo "Sync" para ejecutar los jobs al instante.
- Evalúa que los `AuditLog` y `LogroPersonal` se estén insertando correctamente.
- Imprime por consola el `progreso_actual` y el `progreso_historico` de las metas de cada vendedor (evaluando los slugs).

**Uso:**
```bash
php artisan app:test-eventos
```

---

## 3. `php artisan app:diagnosticar-event-system`
**Diagnóstico Arquitectónico y de Deuda Técnica**

Este comando hace un análisis estático y dinámico del código para asegurarse de que nadie haya roto la arquitectura o vuelto a insertar deuda técnica.

**Lo que revisa:**
- Revisa todos los **Modelos** buscando referencias residuales a la tabla vieja `estatus` (busca la cadena `id_estatus`).
- Verifica si los Listeners y Jobs de la carpeta `app/Listeners` y `app/Jobs` implementan correctamente `ShouldQueue`.
- Valida que el `LogroPersonalLogger` use asincronía (`dispatch()`) para no ralentizar las respuestas HTTP.
- Revisa las reglas de validación en crudo para asegurarse de que no usen reglas lentas sin caché.

**Uso:**
```bash
php artisan app:diagnosticar-event-system
```

---

## Resumen de Flujo de Trabajo Recomendado

1. Cuando bajes cambios fuertes de arquitectura, ejecuta: `php artisan app:diagnosticar-event-system`
2. Si corriste un `migrate:fresh`, puebla la base de datos con: `php artisan app:test-master --no-rollback`
3. Si estás haciendo cambios en Listeners/Observers o Metas, valida que nada se haya roto con: `php artisan app:test-eventos`
