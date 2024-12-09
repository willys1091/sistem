<?php

namespace App\Events;

use App\Models\Estimate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EstimateAcceptedEvent{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $estimate;

    public function __construct(Estimate $estimate){
        $this->estimate = $estimate;
    }
}