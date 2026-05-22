<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * 📡 Contrato de WebSocket (Reverb).
 *
 * Esta sección documenta los canales de broadcasting y eventos en tiempo real.
 * El frontend debe usar Laravel Echo + Reverb para suscribirse.
 *
 * **Autenticación:** Bearer token en el handshake del WebSocket.
 * **Conexión:** `ws://{{host}}:{{port}}/app/{REVERB_APP_KEY}`
 *
 * ---
 * ## ¿Cómo sabe el frontend a qué canales suscribirse?
 *
 * El endpoint `GET /api/me` devuelve `ws_channels`, un array con los canales
 * que el backend asigna según los permisos reales del usuario. El frontend
 * no toma decisiones — solo itera y se suscribe:
 *
 * ```json
 * {
 *   "usuario": { ... },
 *   "ws_channels": [
 *     "private-atenciones",
 *     "private-user.1"
 *   ]
 * }
 * ```
 *
 * | Permiso | Canal asignado |
 * |---|---|
 * | `view:atenciones:todas` | `private-atenciones` + `private-user.{id}` |
 * | `view:atenciones` (sin :todas) | Solo `private-user.{id}` |
 * | Ninguno | Solo `private-user.{id}` (notificaciones directas) |
 */
class BroadcastDocController extends Controller
{
    /**
     * Canal: private-atenciones
     *
     * Transmite en tiempo real los cambios del recurso Atencion (crear, actualizar, eliminar).
     *
     * **Autorización:** `AtencionPolicy::viewAny()` → requiere permiso `view:atenciones`.
     *
     * **Eventos emitidos en este canal:**
     * - `.atencion.created` — Nueva atención creada
     * - `.atencion.updated` — Atención actualizada
     * - `.atencion.deleted` — Atención eliminada (soft-delete)
     *
     * @group WebSocket - Canales
     * @authenticated
     */
    public function canalAtenciones(): JsonResponse
    {
        return response()->json([
            'canal' => 'private-atenciones',
            'autorizacion' => 'AtencionPolicy::viewAny() → permiso view:atenciones',
            'eventos' => [
                '.atencion.created',
                '.atencion.updated',
                '.atencion.deleted',
            ],
        ]);
    }

    /**
     * Canal: private-user.{id}
     *
     * Canal privado por usuario. Un usuario solo puede suscribirse a su propio canal
     * (el `{id}` debe coincidir con el ID del usuario autenticado).
     *
     * **Autorización:** el ID del canal debe coincidir con `auth_user.id`.
     *
     * **Eventos emitidos en este canal:**
     * - `.atencion.created` — Atención asignada al personal dueño del canal
     * - `.atencion.updated` — Atención del personal actualizada
     * - `.atencion.deleted` — Atención del personal eliminada
     *
     * @group WebSocket - Canales
     * @authenticated
     */
    public function canalUsuario(): JsonResponse
    {
        return response()->json([
            'canal' => 'private-user.{id}',
            'parametro' => 'id = ID del usuario autenticado (int)',
            'autorizacion' => 'El ID del canal debe coincidir con el ID del usuario autenticado',
            'eventos' => [
                '.atencion.created',
                '.atencion.updated',
                '.atencion.deleted',
            ],
        ]);
    }

    /**
     * Evento: .atencion.created
     *
     * Emitido cuando se crea una nueva atención. El payload incluye el recurso completo
     * con todas las relaciones precargadas.
     *
     * **Payload (created/updated):**
     * ```json
     * {
     *   "action": "created",
     *   "atencion": { ... recurso AtencionResource completo ... }
     * }
     * ```
     *
     * @group WebSocket - Eventos
     * @authenticated
     */
    public function eventoCreado(): JsonResponse
    {
        return response()->json([
            'evento' => '.atencion.created',
            'canales' => ['private-atenciones', 'private-user.{id_personal}'],
            'payload' => [
                'action' => 'created',
                'atencion' => '{AtencionResource} — recurso completo con relaciones: cliente, personal, origen, estadoAtencion, etapaComercial, cotizaciones',
            ],
        ]);
    }

    /**
     * Evento: .atencion.updated
     *
     * Emitido cuando se actualiza una atención existente. Mismo payload que `created`.
     *
     * **Payload (created/updated):**
     * ```json
     * {
     *   "action": "updated",
     *   "atencion": { ... recurso AtencionResource completo ... }
     * }
     * ```
     *
     * @group WebSocket - Eventos
     * @authenticated
     */
    public function eventoActualizado(): JsonResponse
    {
        return response()->json([
            'evento' => '.atencion.updated',
            'canales' => ['private-atenciones', 'private-user.{id_personal}'],
            'payload' => [
                'action' => 'updated',
                'atencion' => '{AtencionResource} — recurso completo con relaciones',
            ],
        ]);
    }

    /**
     * Evento: .atencion.deleted
     *
     * Emitido cuando se elimina una atención (soft-delete). Payload mínimo: solo ID.
     *
     * **Payload (deleted):**
     * ```json
     * {
     *   "action": "deleted",
     *   "id": 123
     * }
     * ```
     *
     * @group WebSocket - Eventos
     * @authenticated
     */
    public function eventoEliminado(): JsonResponse
    {
        return response()->json([
            'evento' => '.atencion.deleted',
            'canales' => ['private-atenciones', 'private-user.{id_personal}'],
            'payload' => [
                'action' => 'deleted',
                'id' => '{int} — ID de la atención eliminada',
            ],
        ]);
    }
}
