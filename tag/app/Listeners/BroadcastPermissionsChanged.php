<?php

namespace App\Listeners;

use App\Events\PermissionsUpdated;
use App\Models\Usuario;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listens for Spatie Permission events and broadcasts a notification
 * to the frontend so it can refresh the user session.
 */
class BroadcastPermissionsChanged implements ShouldQueue
{
    /**
     * Handle the event.
     * @param mixed $event - Spatie permission event (RoleAttached, PermissionAttached, etc.)
     */
    public function handle($event): void
    {
        $model = $event->model ?? null;

        // Caso 1: El cambio fue directo en un Usuario
        if ($model instanceof Usuario) {
            broadcast(new PermissionsUpdated($model->id));
        } 
        // Caso 2: El cambio fue en un Rol (sincronización de permisos del rol)
        elseif ($model instanceof Role) {
            // Notificamos a todos los usuarios que tienen este rol asignado
            // para que su frontend refresque los permisos en tiempo real.
            $model->users()->chunk(100, function ($users) {
                foreach ($users as $user) {
                    broadcast(new PermissionsUpdated($user->id));
                }
            });
        }
    }
}
