<?php

namespace Modules\Purchase\Entities;

use App\Models\BaseModel;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotations extends BaseModel{
    use HasCompany;

    protected $casts = [
        'expected_date' => 'datetime'
    ];

    public static function lastRequestNumber(){
        return (int)Quotations::max('code');
    }

    public function QuotationApproval(): HasMany{
        return $this->hasMany(QuotationApproval::class);
    }

    public function QuotationDetail(): HasMany{
        return $this->hasMany(QuotationDetail::class);
    }
}