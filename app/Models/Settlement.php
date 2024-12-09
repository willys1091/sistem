<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends BaseModel{
    use CustomFieldsTrait, HasFactory;

    const FILE_PATH = 'settlement-invoice';
    const FILE_PATH_ACC = 'settlement-acc';
    const CUSTOM_FIELD_MODEL = 'App\Models\Settlement';

    public function expense(): BelongsTo{
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function settlementDetail(): HasMany{
        return $this->hasMany(SettlementDetail::class);
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}