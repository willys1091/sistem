<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\CustomFieldsTrait;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Reimbursement extends BaseModel{
    use CustomFieldsTrait, HasFactory, HasCompany;

    const FILE_PATH = 'reimbursement-invoice';
    const FILE_PATH_ACC = 'reimbursement-acc';
    const CUSTOM_FIELD_MODEL = 'App\Models\Reimbursement';

    protected $casts = [
        'purchase_date' => 'datetime',
        'purchase_on' => 'datetime',
    ];
    protected $appends = ['total_amount', 'purchase_on', 'bill_url', 'default_currency_price'];
    protected $with = ['currency', 'company:id'];

    public function getBillUrlAttribute(){
        return ($this->bill) ? asset_url_local_s3(Reimbursement::FILE_PATH . '/' . $this->bill) : '';
    }

    public function currency(): BelongsTo{
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function project(): BelongsTo{
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function client(): BelongsTo{
        return $this->belongsTo(ClientDetails::class, 'client_id');
    }

    public function category(): BelongsTo{
        return $this->belongsTo(ExpensesCategory::class, 'category_id');
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope(ActiveScope::class);
    }

    public function approver(): BelongsTo{
        return $this->belongsTo(User::class, 'approver_id')->withoutGlobalScope(ActiveScope::class);
    }

    public function recurrings(): HasMany{
        return $this->hasMany(Reimbursement::class, 'parent_id');
    }

    public function ReimbursementApproval(): HasMany{
        return $this->hasMany(ReimbursementApproval::class);
    }

    public function ReimbursementDetail(): HasMany{
        return $this->hasMany(ReimbursementDetail::class);
    }

    public function transactions(): HasMany{
        return $this->hasMany(BankTransaction::class, 'reimbursement_id');
    }

    public function getTotalAmountAttribute(){
        if (!is_null($this->price) && !is_null($this->currency_id)) {
            return currency_format($this->price, $this->currency_id);
        }

        return '';
    }

    public function getPurchaseOnAttribute(){
        if (is_null($this->purchase_date)) {
            return '';
        }
        return $this->purchase_date->format($this->company ? $this->company->date_format : company()->date_format);
    }

    public function mentionUser(): BelongsToMany{
        return $this->belongsToMany(User::class, 'mention_users')->withoutGlobalScope(ActiveScope::class)->using(MentionUser::class);
    }

    public function defaultCurrencyPrice() : Attribute{
        return Attribute::make(
            get: function () {
                if ($this->currency_id == company()->currency_id) {
                    return $this->price;
                }

                if(!$this->exchange_rate){
                    return $this->price;
                }
                return ($this->price * ((1/(float)$this->exchange_rate)));
            },
        );
    }

    public function bankAccount(){
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}