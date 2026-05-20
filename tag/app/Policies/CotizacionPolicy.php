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
        if ($usuario->can('view:cotizaciones')) {
            return true;
        }

        return $cotizacion->atencion
            && (int) $cotizacion->atencion->id_personal === (int) $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->can('create:cotizaciones');
    }

    public function update(Usuario $usuario, Cotizacion $cotizacion): bool
    {
        if ($usuario->can('edit:cotizaciones')) {
            return true;
        }

        return $cotizacion->atencion
            && (int) $cotizacion->atencion->id_personal === (int) $usuario->id;
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
}
