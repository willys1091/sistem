<?php

namespace Modules\Purchase\Listeners;

use Modules\Purchase\Events\NewPurchaseRequestEvent;
use Modules\Purchase\Notifications\NewPurchaseRequest;
use Illuminate\Support\Facades\Notification;
use Modules\Purchase\Events\NewPurchaseRequestEvent as EventsNewPurchaseRequestEvent;

class NewPurchaseRequestListener{
    public function handle(EventsNewPurchaseRequestEvent $event)
    {
        if ($event->notifyUser->email != null) {
            Notification::send($event->notifyUser, new NewPurchaseRequest($event->request));
        }
    }
}
