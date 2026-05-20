<?php

namespace App\Policies;

use App\Models\Atencion;
use App\Models\Usuario;

class AtencionPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:atenciones');
    }

    public function view(Usuario $usuario, Atencion $atencion): bool
    {
        return $usuario->can('view:atenciones')
            || (int) $atencion->id_personal === (int) $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:atenciones');
    }

    public function update(Usuario $usuario, Atencion $atencion): bool
    {
        return $usuario->can('edit:atenciones')
            || (int) $atencion->id_personal === (int) $usuario->id;
    }

    public function delete(Usuario $usuario, Atencion $atencion): bool
    {
        return $usuario->can('delete:atenciones');
    }
}
