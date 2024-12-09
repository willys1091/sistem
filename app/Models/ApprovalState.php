<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalState extends Model{
    use HasFactory;
    protected $table = 'approval_state';
    public $timestamps = false;

    public function approval(){
        return $this->belongsTo('App\Models\approval','approval_id');
    }

    public function approval_act(){
        return $this->HasMany('App\Models\approval_act');
    }
}
