<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExcelProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public string $filePathHash;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $data, string $filePathHash)
    {
        $this->data = $data;
        $this->filePathHash = $filePathHash;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('excel-processed.' . $this->filePathHash);
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->data,
        ];
    }
}
