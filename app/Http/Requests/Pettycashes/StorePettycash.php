<?php

namespace App\Http\Requests\Pettycashes;

use App\Models\BankAccount;
use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;

class StorePettycash extends CoreRequest{
    use CustomFieldsRequestTrait;

    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'item_name' => 'required',
            'category_id' => 'required',
            'purchase_date' => 'required',
            'user_id' => 'required',
            'price' => 'required|numeric',
            'currency_id' => 'required',
            'payee' => 'required',
            'bank_account' => 'required',
            'bank_name' => 'required',
            'bill' => 'required',
        ];

        $rules = $this->customFieldRules($rules);

        if (request('bank_account_id') != '') {
            $bankBalance = BankAccount::findOrFail(request('bank_account_id'));

            $rules['price'] = 'required|numeric|max:'.$bankBalance->bank_balance;
        }
        return $rules;
    }

    public function attributes(){
        $attributes = [];
        $attributes = $this->customFieldsAttributes($attributes);
        return $attributes;
    }
}