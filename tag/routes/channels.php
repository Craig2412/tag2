<?php

use App\Models\Atencion;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private channel for user-specific notifications.
 * A user can only subscribe to their own channel.
 * The {id} must match the authenticated user's ID.
 */
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private channel for real-time Atencion updates.
 * Delegates to AtencionPolicy::viewAny() — covers both view:atenciones
 * permission and personal role in a single source of truth.
 */
Broadcast::channel('atenciones', function ($user) {
    return $user->can('viewAny', Atencion::class);
});
