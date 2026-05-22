# Plan de Implementación: Conciliación Bancaria Automática (Futuro)

Este documento detalla la hoja de ruta arquitectónica para cuando el sistema requiera automatizar la conciliación de los pagos de clientes (Ingresos) con entidades bancarias externas.

Actualmente (Fase 1), el sistema de pagos descuenta la deuda de las Órdenes de Compra inmediatamente basándose en si el registro del pago existe (soft-deletes). Para la Fase 2 (Conciliación Estricta), la deuda solo debe descontarse cuando el pago pase a estatus `conciliado`.

Gracias a la migración del campo genérico `estatus` hacia la tabla de catálogo fuertemente tipada `estados_conciliacion`, la transición solo requerirá editar **3 puntos clave** en la aplicación:

## 1. El cálculo en el Backend (Capa de Servicios)
En el archivo `App\Services\EstadoFaseService.php`, el método `sincronizarEstadoFinanciero()` es el cerebro que decide si una Orden de Compra está pagada o pendiente.

**Cambio a realizar:**
Modificar la suma para que filtre utilizando Eloquent los pagos que están estrictamente atados al slug `conciliado`.

```php
// ANTES (Fase 1):
$totalPagado = $orden->pagos()->sum('monto_asignado');

// DESPUÉS (Fase 2 - Estricto):
$totalPagado = $orden->pagos()
    ->whereHas('pago.estadoConciliacion', function($q) {
        $q->where('slug', 'conciliado');
    })->sum('monto_asignado');
```

## 2. El cálculo en el Frontend (Model Accessor)
En el archivo `App\Models\OrdenCompra.php`, existe un atributo calculado que le indica al Frontend cuánto dinero en vivo ya fue abonado a la factura. Este debe coincidir matemáticamente con el backend.

**Cambio a realizar:**
Actualizar el accesor `getTotalPagadoAttribute()` aplicando el mismo filtro.

```php
// En App\Models\OrdenCompra.php
public function getTotalPagadoAttribute(): float
{
    return (float) $this->pagos()
        ->whereHas('pago.estadoConciliacion', function($q) {
            $q->where('slug', 'conciliado');
        })->sum('monto_asignado');
}
```

## 3. El Disparador del Recálculo (El Motor del Job)
Actualmente, el que obliga al sistema a recalcular la deuda es el observer de la tabla pivote (`PagoOrdenCompraObserver`), ya que asume que "si hay un nuevo registro en el pivote, entró dinero nuevo".

En el modelo de conciliación, el pivote ya existe, lo que cambia es el estado del padre (`Pago`).

**Cambio a realizar:**
Crear un Observer para el modelo `Pago` (o despachar un evento directamente desde el Job de conciliación bancaria) que atrape la actualización del estado.

```php
// En App\Observers\PagoObserver.php
public function updated(Pago $pago): void
{
    // Si el pago cambió de "por_conciliar" a "conciliado" (o fue "rechazado")
    if ($pago->wasChanged('id_estado_conciliacion')) {
        
        // 1. Buscamos todas las ordenes de compra conectadas a este pago
        $ordenesAfectadas = $pago->ordenesCompra()->with('ordenCompra')->get();

        // 2. Le pedimos al servicio que recalcule la deuda real de cada una
        foreach ($ordenesAfectadas as $pivote) {
            if ($pivote->ordenCompra) {
                \App\Services\EstadoFaseService::sincronizarEstadoFinanciero($pivote->ordenCompra);
            }
        }
    }
}
```

### Resumen del Flujo Futuro:
1. El usuario registra un pago manual -> Nace con slug `por_conciliar`.
2. El Job revisa la API del banco a la medianoche.
3. El Job encuentra la transacción y actualiza `$pago->update(['id_estado_conciliacion' => $idConciliado])`.
4. El `PagoObserver` atrapa la actualización.
5. El `EstadoFaseService` hace la suma ignorando los pagos que no son `conciliado`.
6. El estado de la Orden de Compra cambia de "Parcial" a "Pagado" y el cliente ve su deuda en $0 en tiempo real.
