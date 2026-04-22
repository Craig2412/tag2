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
        $user = $event->user;

        if ($user instanceof Usuario) {
            // Signal the frontend that permissions have changed
            broadcast(new PermissionsUpdated($user->id))->toOthers();
            
            Log::info("Permissions changed for user ID: {$user->id}. Broadcast emitted.");
        }
    }
}
