<?php

namespace App\Policies;

use App\Models\Atencion;
use App\Models\Usuario;

class AtencionPolicy
{
    /**
     * Cualquier usuario con view:atenciones puede ver el listado.
     * El alcance (propias vs. todas) se resuelve en view().
     */
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:atenciones');
    }

    public function view(Usuario $usuario, Atencion $atencion): bool
    {
        // Escalación: permiso :todas da acceso global
        if ($usuario->can('view:atenciones:todas')) {
            return true;
        }

        // Base: solo las propias
        return $usuario->can('view:atenciones')
            && $this->esPersonalAsignado($usuario, $atencion);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:atenciones');
    }

    public function update(Usuario $usuario, Atencion $atencion): bool
    {
        // Escalación: permiso :todas permite editar cualquier atención
        if ($usuario->can('edit:atenciones:todas')) {
            return true;
        }

        // Base: solo las propias
        return $usuario->can('edit:atenciones')
            && $this->esPersonalAsignado($usuario, $atencion);
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
