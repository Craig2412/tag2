<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/**
 * @group Infraestructura
 *
 * Canales de comunicación en tiempo real y configuración técnica.
 */
class BroadcastingController extends Controller
{
    /**
     * Autorizar suscripción a WebSockets (Reverb).
     *
     * Este endpoint es el puente de autenticación para Laravel Echo. Permite que el
     * frontend de Next.js valide si un usuario tiene permiso para escuchar un
     * canal privado específico.
     *
     * Internamente, utiliza los permisos definidos en `routes/channels.php`.
     *
     * @authenticated
     *
     * @subgroup WebSockets
     *
     * @bodyParam channel_name string required El nombre completo del canal al que se desea suscribir. Example: private-user.1
     * @bodyParam socket_id string required El ID de conexión generado por el cliente WebSocket. Example: 12345.67890
     *
     * @response {
     *   "auth": "nolr4mpzzy6fit7ilrkz:9243d2e63da04ef7c1715a3eff375023e642eb4fb7981390c4c84334f2d23c4c"
     * }
     * @response 403 {
     *   "message": "No autorizado para este canal."
     * }
     */
    public function auth(Request $request)
    {
        // Forzamos el uso del guard de sanctum para este proceso de autorización
        Auth::shouldUse('sanctum');

        return Broadcast::auth($request);
    }
}
