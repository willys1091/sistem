<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestAct extends Model{
    use HasFactory;
    protected $table = 'purchase_request_act';
    public $timestamps = false;
}
