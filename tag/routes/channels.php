<?php

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
 * Only users with the view:atenciones permission can subscribe.
 * Personal users can only see their own atenciones (enforced by Policy).
 */
Broadcast::channel('atenciones', function ($user) {
    return $user->can('view:atenciones');
});
