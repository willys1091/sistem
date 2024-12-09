<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuotationDetail extends Model{
    use HasFactory;
    protected $table = 'quotation_detail';
    public $timestamps = false;

    public function quotations(): BelongsTo{
        return $this->belongsTo(Quotations::class, 'header_id');
    }
}