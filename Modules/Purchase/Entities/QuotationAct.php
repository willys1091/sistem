<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationAct extends Model{
    use HasFactory;
    protected $table = 'quotation_act';
    public $timestamps = false;
}
