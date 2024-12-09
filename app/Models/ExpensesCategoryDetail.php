<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpensesCategoryDetail extends BaseModel{
    use HasCompany;

    protected $table = 'expenses_category_detail';
    protected $default = ['id', 'category_name'];

    public function expensesDetail(): HasMany{
        return $this->hasMany(ExpenseDetail::class, 'category_id');
    }

    public function settlementsDetail(): HasMany{
        return $this->hasMany(SettlementDetail::class, 'category_id');
    }
}