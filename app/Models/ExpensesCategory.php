<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpensesCategory extends BaseModel{
    use HasCompany;

    protected $table = 'expenses_category';
    protected $default = ['id', 'category_name'];

    public function expenses(): HasMany{
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function roles(): HasMany{
        return $this->hasMany(ExpensesCategoryRole::class, 'expenses_category_id');
    }
}