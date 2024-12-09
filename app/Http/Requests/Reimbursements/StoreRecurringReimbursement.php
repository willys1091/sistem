<?php

namespace App\Http\Requests\Reimbursements;

use App\Http\Requests\CoreRequest;
use App\Models\BankAccount;

class StoreRecurringReimbursement extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        $rotation = $this->get('rotation');

        $rules = [
            'item_name' => 'required',
            'user_id' => 'required',
            'price' => 'required|numeric',
            'billing_cycle' => 'required',
        ];


        if (request('bank_account_id') != '') {
            $bankBalance = BankAccount::findOrFail(request('bank_account_id'));
            $rules['price'] = 'required|numeric|max:'.$bankBalance->bank_balance;
        }
        return $rules;
    }
}