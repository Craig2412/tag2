<?php

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\Usuario;

class OrdenCompraPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:ordenes_compra');
    }

    public function view(Usuario $usuario, OrdenCompra $orden): bool
    {
        // Escalación: permiso :todas da acceso global
        if ($usuario->can('view:ordenes_compra:todas')) {
            return true;
        }

        // Base: solo OCs de sus propias atenciones
        return $usuario->can('view:ordenes_compra')
            && $this->esPersonalAsignado($usuario, $orden);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:ordenes_compra');
    }

    public function update(Usuario $usuario, OrdenCompra $orden): bool
    {
        // Escalación: permiso :todas permite editar cualquier OC
        if ($usuario->can('edit:ordenes_compra:todas')) {
            return true;
        }

        // Base: solo OCs de sus propias atenciones
        return $usuario->can('edit:ordenes_compra')
            && $this->esPersonalAsignado($usuario, $orden);
    }

    public function delete(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('delete:ordenes_compra');
    }

    public function approve(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('edit:ordenes_compra:todas');
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
