<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\Usuario;

class CotizacionPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:cotizaciones');
    }

    public function view(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        // Escalación: permiso :todas da acceso global
        if ($usuario->can('view:cotizaciones:todas')) {
            return true;
        }

        // Base: solo cotizaciones de sus propias atenciones
        return $usuario->can('view:cotizaciones')
            && $this->esPersonalAsignado($usuario, $cotizacion);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:cotizaciones');
    }

    public function update(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        // Escalación: permiso :todas permite editar cualquier cotización
        if ($usuario->can('edit:cotizaciones:todas')) {
            return true;
        }

        // Base: solo cotizaciones de sus propias atenciones
        return $usuario->can('edit:cotizaciones')
            && $this->esPersonalAsignado($usuario, $cotizacion);
    }

    public function delete(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        return $usuario->can('delete:cotizaciones');
    }

    /** Solo usuarios con permiso :todas pueden aprobar cotizaciones */
    public function approve(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        return $usuario->can('edit:cotizaciones:todas');
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
