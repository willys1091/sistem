<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseDetail extends Model{
    use HasFactory;

    protected $casts = [
        'estdate' => 'date'
    ];

    const CUSTOM_FIELD_MODEL = 'App\Models\ExpenseDetail';
    protected $table = 'expense_detail';

    public function expense(){
        return $this->belongsTo('App\Models\Expense','header_id');
    }

    public function expenseCategoryDetail(){
        return $this->belongsTo('App\Models\ExpensesCategoryDetail','category_id');
    }

    public function getTotalAmountAttribute(){
        return currency_format($this->amount);
    }
}