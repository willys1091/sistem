<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementAct extends Model{
    use HasFactory;
    protected $table = 'reimbursement_act';
    public $timestamps = false;
}
