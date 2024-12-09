<?php

namespace App\Http\Requests\Settlements;

use App\Http\Requests\CoreRequest;

class StoreSettlement extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'expense_id' => 'required'
        ];

        return $rules;
    }

}
