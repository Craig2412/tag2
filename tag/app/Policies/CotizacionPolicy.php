<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\Usuario;

class CotizacionPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:cotizaciones')
            || $usuario->hasRole('personal');
    }

    public function view(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        if ($usuario->can('view:cotizaciones')) {
            return true;
        }

        // Personal: solo puede ver cotizaciones de sus propias atenciones
        return $this->esPersonalAsignado($usuario, $cotizacion);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:cotizaciones')
            || $usuario->hasRole('personal');
    }

    public function update(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        if ($usuario->can('edit:cotizaciones')) {
            return true;
        }

        // Personal: solo puede editar cotizaciones de sus propias atenciones
        return $this->esPersonalAsignado($usuario, $cotizacion);
    }

    public function delete(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        return $usuario->can('delete:cotizaciones');
    }

    /** Solo usuarios con permiso explícito pueden aprobar cotizaciones */
    public function approve(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        return $usuario->can('edit:cotizaciones');
    }

    /**
     * Verifica que el usuario autenticado sea el personal asignado a la atención
     * padre de esta cotización. Navega: cotizacion -> atencion -> personal -> usuario_id.
     */
    private function esPersonalAsignado(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        if (! $cotizacion->id_atencion) {
            return false;
        }

        $cotizacion->loadMissing('atencion.personal');

        return $cotizacion->atencion
            && $cotizacion->atencion->personal
            && (int) $cotizacion->atencion->personal->usuario_id === (int) $usuario->id;
    }
}
