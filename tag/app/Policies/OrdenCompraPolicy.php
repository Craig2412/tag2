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
        if ($usuario->can('view:ordenes_compra')) {
            return true;
        }

        return $orden->cotizacion
            && $orden->cotizacion->atencion
            && (int) $orden->cotizacion->atencion->id_personal === (int) $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:ordenes_compra');
    }

    public function update(Usuario $usuario, OrdenCompra $orden): bool
    {
        if ($usuario->can('edit:ordenes_compra')) {
            return true;
        }

        return $orden->cotizacion
            && $orden->cotizacion->atencion
            && (int) $orden->cotizacion->atencion->id_personal === (int) $usuario->id;
    }

    public function delete(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('delete:ordenes_compra');
    }

    public function approve(Usuario $usuario, OrdenCompra $orden): bool
    {
        return $usuario->can('edit:ordenes_compra');
    }
}
