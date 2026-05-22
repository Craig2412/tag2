<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user's roles or permissions change.
 * The frontend listens to this event to trigger a silent session refresh.
 * The payload is intentionally minimal: only the user ID is sent.
 * The frontend will fetch fresh data securely via HTTPS/Server Action.
 */
class PermissionsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  int  $userId  - The ID of the user whose permissions changed.
     */
    public function __construct(public readonly int $userId)
    {
        //
    }

    /**
     * Private channel scoped to the specific user.
     * Requires authentication via routes/channels.php.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    /**
     * Custom event name to avoid PHP namespace in the client listener.
     */
    public function broadcastAs(): string
    {
        return 'PermissionsUpdated';
    }

    /**
     * Only broadcast the user ID. Never send sensitive data over the wire.
     */
    public function broadcastWith(): array
    {
        return ['userId' => $this->userId];
    }
}
