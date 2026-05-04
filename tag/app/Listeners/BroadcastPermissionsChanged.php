<?php

namespace App\Listeners;

use App\Events\PermissionsUpdated;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;

/**
 * Listens for Spatie Permission events and broadcasts a notification
 * to the frontend so it can refresh the user session.
 */
class BroadcastPermissionsChanged
{
    /**
     * Handle the event.
     * @param mixed $event - Spatie permission event (RoleAssigned, RoleRemoved, etc.)
     */
    public function handle($event): void
    {
        $user = $event->model ?? null;

        if ($user instanceof Usuario) {
            broadcast(new PermissionsUpdated($user->id));
        }
    }
}
