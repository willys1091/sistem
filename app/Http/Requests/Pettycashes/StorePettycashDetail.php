<?php

namespace App\Http\Requests\Pettycashes;

use App\Http\Requests\CoreRequest;

class StorePettycashDetail extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        $rules = [
            'remarks' => 'required',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'estdate' => 'required'
        ];

        return $rules;
    }

}
