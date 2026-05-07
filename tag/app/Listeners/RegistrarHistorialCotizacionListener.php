<?php

namespace App\Listeners;

use App\Events\CotizacionGuardado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RegistrarHistorialCotizacionListener
{
    public function handle(CotizacionGuardado $event): void
    {
        // OBSOLETO: Se eliminó la lógica porque causaba duplicidad de eventos.
        // Ahora se usa exclusivamente RegistrarHistorialEstatusCotizacionListener.
    }
}
