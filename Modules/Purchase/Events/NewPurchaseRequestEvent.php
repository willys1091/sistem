<?php

namespace Modules\Purchase\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Purchase\Entities\PurchaseRequest;

class NewPurchaseRequestEvent{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;
    public $notifyUser;

    public function __construct(PurchaseRequest $request, $notifyUser){
        $this->request = $request;
        $this->notifyUser = $notifyUser;
    }
}