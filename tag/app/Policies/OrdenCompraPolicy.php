<?php

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\Usuario;

class OrdenCompraPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:ordenes_compra')
            || $usuario->hasRole('personal');
    }

    public function view(Usuario $usuario, OrdenCompra $orden): bool
    {
        if ($usuario->can('view:ordenes_compra')) {
            return true;
        }

        // Personal: solo puede ver OCs de sus propias atenciones
        return $this->esPersonalAsignado($usuario, $orden);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:ordenes_compra')
            || $usuario->hasRole('personal');
    }

    public function update(Usuario $usuario, OrdenCompra $orden): bool
    {
        if ($usuario->can('edit:ordenes_compra')) {
            return true;
        }

        // Personal: solo puede editar OCs de sus propias atenciones
        return $this->esPersonalAsignado($usuario, $orden);
    }

    public function delete(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('delete:ordenes_compra');
    }

    public function approve(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('edit:ordenes_compra');
    }

    /**
     * Verifica que el usuario autenticado sea el personal asignado a la atención
     * padre de esta OC. Navega: ordenCompra -> cotizacion -> atencion -> personal -> usuario_id.
     */
    private function esPersonalAsignado(Usuario $usuario, OrdenCompra $orden): bool
    {
        if (! $orden->id_cotizacion) {
            return false;
        }

        $orden->loadMissing('cotizacion.atencion.personal');

        return $orden->cotizacion
            && $orden->cotizacion->atencion
            && $orden->cotizacion->atencion->personal
            && (int) $orden->cotizacion->atencion->personal->usuario_id === (int) $usuario->id;
    }
}
