<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettycashesCategoryDetail extends BaseModel{
    use HasCompany;

    protected $table = 'pettycashes_category_detail';
    protected $default = ['id', 'category_name'];

    public function pettycashesDetail(): HasMany{
        return $this->hasMany(PettycashDetail::class, 'category_id');
    }
}