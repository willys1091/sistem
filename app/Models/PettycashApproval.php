<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettycashApproval extends Model{
    use HasFactory;
    protected $table = 'pettycash_approval';
    public $timestamps = false;

    public function pettycash(){
        return $this->belongsTo('App\Models\Pettycash','header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}
