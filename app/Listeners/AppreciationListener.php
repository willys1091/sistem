<?php

namespace App\Listeners;

use App\Events\AppreciationEvent;
use App\Notifications\NewAppreciation;
use Illuminate\Support\Facades\Notification;

class AppreciationListener{
    public function handle(AppreciationEvent $event){
        Notification::send($event->notifyUser, new NewAppreciation($event->userAppreciation));
    }
}