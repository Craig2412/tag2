<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\Usuario;

class PagoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:pagos')
            || $usuario->hasRole('personal');
    }

    public function view(Usuario $usuario, Pago $pago): bool
    {
        if ($usuario->can('view:pagos')) {
            return true;
        }

        // Personal: solo puede ver pagos de OCs de sus propias atenciones
        return $this->esPersonalAsignado($usuario, $pago);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:pagos')
            || $usuario->hasRole('personal');
    }

    public function update(Usuario $usuario, Pago $pago): bool
    {
        if ($usuario->can('edit:pagos')) {
            return true;
        }

        return $this->esPersonalAsignado($usuario, $pago);
    }

    public function delete(Usuario $usuario, Pago $pago): bool
    {
        return $usuario->can('delete:pagos');
    }

    /**
     * Verifica que el usuario autenticado sea el personal asignado a la atención
     * dueña de este pago. Navega: pago -> ordenesCompra -> ordenCompra -> cotizacion -> atencion -> personal -> usuario_id.
     */
    private function esPersonalAsignado(Usuario $usuario, Pago $pago): bool
    {
        $pago->loadMissing('ordenesCompra.ordenCompra.cotizacion.atencion.personal');

        foreach ($pago->ordenesCompra as $pagoOC) {
            if ($pagoOC->ordenCompra
                && $pagoOC->ordenCompra->cotizacion
                && $pagoOC->ordenCompra->cotizacion->atencion
                && $pagoOC->ordenCompra->cotizacion->atencion->personal
                && (int) $pagoOC->ordenCompra->cotizacion->atencion->personal->usuario_id === (int) $usuario->id
            ) {
                return true;
            }
        }

        return false;
    }
}
