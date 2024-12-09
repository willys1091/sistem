<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BirthdayReminderEvent{

    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $upcomingBirthdays;
    public $company;

    public function __construct($company, $upcomingBirthdays){
        $this->upcomingBirthdays = $upcomingBirthdays;
        $this->company = $company;
    }
}