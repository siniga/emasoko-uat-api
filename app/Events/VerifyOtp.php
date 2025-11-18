<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerifyOtp implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $payload;
    public $account_number;
    public $mandate_reference;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($payload, $account_number, $mandate_reference)
    {
        $this->payload =  $payload;
        $this->account_number = $account_number;
        $this->mandate_reference = $mandate_reference;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('verify-otp');
    }
}
