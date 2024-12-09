<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettycashDetail extends Model{
    use HasFactory;

    protected $casts = [
        'estdate' => 'date'
    ];

    const CUSTOM_FIELD_MODEL = 'App\Models\Detail';
    protected $table = 'pettycash_detail';

    public function pettycash(){
        return $this->belongsTo('App\Models\Pettycash','header_id');
    }

    public function pettycashCategoryDetail(){
        return $this->belongsTo('App\Models\PettycashCategoryDetail','category_id');
    }

    public function getTotalAmountAttribute(){
        return currency_format($this->amount);
    }
}