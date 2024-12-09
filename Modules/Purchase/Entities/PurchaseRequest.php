<?php

namespace Modules\Purchase\Entities;

use App\Models\BaseModel;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseRequest extends BaseModel{
    use HasCompany;

    protected $casts = [
        'request_date' => 'datetime',
        'estimation_delivery_date' => 'datetime'
    ];

    public static function lastRequestNumber(){
        return (int)PurchaseRequest::max('code');
    }

    public function PurchaseRequestApproval(): HasMany{
        return $this->hasMany(PurchaseRequestApproval::class);
    }

    public function PurchaseRequestItem(): HasMany{
        return $this->hasMany(PurchaseRequestItem::class);
    }
}