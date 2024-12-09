<?php

namespace App\Http\Requests\Settlements;

use App\Http\Requests\CoreRequest;

class StoreSettlementDetail extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'remarks' => 'required',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'estdate' => 'required',
            'bill' => 'required'
        ];

        return $rules;
    }

}
