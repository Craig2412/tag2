<?php

namespace App\Policies;

use App\Models\Atencion;
use App\Models\Usuario;

class AtencionPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:atenciones')
            || $usuario->hasRole('personal');
    }

    public function view(Usuario $usuario, Atencion $atencion): bool
    {
        if ($usuario->can('view:atenciones')) {
            return true;
        }

        // Personal: solo puede ver sus propias atenciones
        return $this->esPersonalAsignado($usuario, $atencion);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:atenciones')
            || $usuario->hasRole('personal');
    }

    public function update(Usuario $usuario, Atencion $atencion): bool
    {
        if ($usuario->can('edit:atenciones')) {
            return true;
        }

        // Personal: solo puede editar sus propias atenciones
        return $this->esPersonalAsignado($usuario, $atencion);
    }

    public function delete(Usuario $usuario, Atencion $atencion): bool
    {
        return $usuario->can('delete:atenciones');
    }

    /**
     * Verifica si el usuario autenticado es el personal asignado a la atención.
     * Compara usuario.id con personal.usuario_id (no con atencion.id_personal).
     */
    private function esPersonalAsignado(Usuario $usuario, Atencion $atencion): bool
    {
        if (! $atencion->id_personal) {
            return false;
        }

        $atencion->loadMissing('personal');

        return $atencion->personal
            && (int) $atencion->personal->usuario_id === (int) $usuario->id;
    }
}
