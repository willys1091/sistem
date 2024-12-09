<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementAct extends Model{
    use HasFactory;
    protected $table = 'settlement_act';
    public $timestamps = false;
}