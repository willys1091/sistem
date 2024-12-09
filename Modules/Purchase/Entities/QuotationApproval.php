<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuotationApproval extends Model{
    use HasFactory;
    protected $table = 'quotation_approval';
    public $timestamps = false;

    public function quotations(): BelongsTo{
        return $this->belongsTo(Quotations::class, 'header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}