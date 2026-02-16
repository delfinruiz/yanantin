<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public int $eventId;

    public function __construct(string $action, int $eventId)
    {
        $this->action = $action;
        $this->eventId = $eventId;
    }

    public function broadcastOn(): array
    {
        return [new Channel('events')];
    }

    public function broadcastAs(): string
    {
        return 'EventChanged';
    }
}
