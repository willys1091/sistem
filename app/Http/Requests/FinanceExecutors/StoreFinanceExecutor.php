<?php

namespace App\Http\Requests\FinanceExecutors;

use App\Http\Requests\CoreRequest;

class StoreFinanceExecutor extends CoreRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'transferDate' => 'required',
            'bill' => 'required',
        ];
        return $rules;

    }
}