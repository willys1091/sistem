<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequestApproval extends Model{
    use HasFactory;
    protected $table = 'purchase_request_approval';
    public $timestamps = false;

    public function purchaseRequest(): BelongsTo{
        return $this->belongsTo(PurchaseRequest::class, 'header_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }
}