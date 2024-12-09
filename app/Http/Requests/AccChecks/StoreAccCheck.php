<?php

namespace App\Http\Requests\AccChecks;

use App\Http\Requests\CoreRequest;

class StoreAccCheck extends CoreRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'accNo' => 'required',
        ];
        return $rules;

    }
}