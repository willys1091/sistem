<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalAct extends Model{
    use HasFactory;
    protected $table = 'approval_act';
    public $timestamps = false;

    public function approval_state(){
        return $this->belongsTo('App\Models\approval_state','state_id');
    }

    public function next_states(){
        return $this->belongsTo('App\Models\approval_state','next_state');
    }
}
