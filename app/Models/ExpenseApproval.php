<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseApproval extends Model{
    use HasFactory;
    protected $table = 'expense_approval';
    public $timestamps = false;

    public function expense(){
        return $this->belongsTo('App\Models\Expense','header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}
