<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\CustomFieldsTrait;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementRecurring extends BaseModel{
    use CustomFieldsTrait, HasCompany;

    protected $casts = [
        'issue_date' => 'datetime',
        'created_at' => 'datetime',
        'next_reimbursement_date' => 'datetime',
    ];
    protected $with = ['currency', 'company:id'];

    protected $appends = ['total_amount', 'created_on', 'bill_url'];

    protected $table = 'reimbursement_recurring';

    const ROTATION_COLOR = [
        'daily' => 'success',
        'weekly' => 'info',
        'monthly' => 'secondary',
        'bi-weekly' => 'warning',
        'quarterly' => 'light',
        'half-yearly' => 'dark',
        'annually' => 'success',
    ];

    public function currency(): BelongsTo{
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope(ActiveScope::class);
    }

    public function project(): BelongsTo{
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function createdBy(): BelongsTo{
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScope(ActiveScope::class);
    }

    public function category(): BelongsTo{
        return $this->belongsTo(ReimbursementsCategory::class, 'category_id');
    }

    public function recurrings(): HasMany{
        return $this->hasMany(Reimbursement::class, 'reimbursements_recurring_id');
    }

    public function bank(): BelongsTo{
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function getTotalAmountAttribute(){
        if (!is_null($this->price) && !is_null($this->currency_id)) {
            return currency_format($this->price, $this->currency->id);
        }

        return '';
    }

    public function getCreatedOnAttribute(){
        if (!is_null($this->created_at)) {
            return $this->created_at->format($this->company->date_format);
        }

        return '';
    }

    public function getBillUrlAttribute(){
        return ($this->bill) ? asset_url_local_s3(Reimbursement::FILE_PATH . '/' . $this->bill) : '';
    }

}