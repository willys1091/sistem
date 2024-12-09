<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementDetail extends Model{
    use HasFactory;

    protected $casts = [
        'estdate' => 'date'
    ];

    const CUSTOM_FIELD_MODEL = 'App\Models\ReimbursementDetail';
    protected $table = 'reimbursement_detail';

    public function reimbursement(){
        return $this->belongsTo('App\Models\Reimbursement','header_id');
    }

    public function reimbursementsCategoryDetail(){
        return $this->belongsTo('App\Models\ReimbursementsCategoryDetail','category_id');
    }

    public function getTotalAmountAttribute(){
        return currency_format($this->amount);
    }
}