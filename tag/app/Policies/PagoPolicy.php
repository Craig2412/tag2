<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\Usuario;

class PagoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->can('view:pagos');
    }

    public function view(Usuario $usuario, Pago $pago): bool
    {
        return $usuario->can('view:pagos')
            || (int) $pago->id_usuario === (int) $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:pagos');
    }

    public function update(Usuario $usuario, Pago $pago): bool
    {
        return $usuario->can('edit:pagos')
            || (int) $pago->id_usuario === (int) $usuario->id;
    }

    public function delete(Usuario $usuario, Pago $pago): bool
    {
        return $usuario->can('delete:pagos');
    }
}
