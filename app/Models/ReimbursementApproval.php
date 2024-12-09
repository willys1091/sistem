<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementApproval extends Model{
    use HasFactory;
    protected $table = 'reimbursement_approval';
    public $timestamps = false;

    public function reimbursement(){
        return $this->belongsTo('App\Models\Reimbursement','header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}
