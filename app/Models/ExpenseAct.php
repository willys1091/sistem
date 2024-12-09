<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseAct extends Model{
    use HasFactory;
    protected $table = 'expense_act';
    public $timestamps = false;
}
