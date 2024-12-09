<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettycashAct extends Model{
    use HasFactory;
    protected $table = 'pettycash_act';
    public $timestamps = false;
}
