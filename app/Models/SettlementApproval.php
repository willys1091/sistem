<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementApproval extends Model{
    use HasFactory;
    protected $table = 'settlement_approval';
    public $timestamps = false;

    public function settlement(){
        return $this->belongsTo('App\Models\settlement','header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}