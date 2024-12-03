<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExcelProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

//    public $data;
    public string $fileHash;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $fileHash)
    {
        Log::info('=============ExcelProcessed | __construct==================================');
        Log::info('fileHash: '.$fileHash);
//        $this->data = $data;
        $this->fileHash = $fileHash;
    }

    public function broadcastAs()
    {
        return 'ExcelProcessed';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        Log::info('=============ExcelProcessed | broadcastOn==================================');
        return new Channel("excel-processed.{$this->fileHash}");
    }

    public function broadcastWith()
    {
        Log::info('=============ExcelProcessed | broadcastWith==================================');
        return [
            'data' => $this->fileHash,
        ];
    }
}
