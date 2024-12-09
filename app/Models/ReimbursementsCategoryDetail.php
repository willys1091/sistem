<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementsCategoryDetail extends BaseModel{
    use HasCompany;

    protected $table = 'reimbursements_category_detail';
    protected $default = ['id', 'category_name'];

    public function reimbursementsDetail(): HasMany{
        return $this->hasMany(ReimbursementDetail::class, 'category_id');
    }
}