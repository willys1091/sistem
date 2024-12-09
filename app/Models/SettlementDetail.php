<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementDetail extends Model{
    use HasFactory;

    protected $casts = [
        'estdate' => 'date'
    ];
    
    protected $table = 'settlement_detail';

    public function settlement(){
        return $this->belongsTo('App\Models\settlement','header_id');
    }

    public function expenseCategoryDetail(){
        return $this->belongsTo('App\Models\ExpensesCategoryDetail','category_id');
    }

    public function getTotalAmountAttribute(){
        return currency_format($this->amount);
    }
}