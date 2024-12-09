<?php

namespace Modules\Purchase\Http\Requests\PurchaseRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDetailRequest extends FormRequest{   
    public function rules(){
        $setting = company();
        
        $rules = [
            'product_id' => 'required',
            'qty' => 'required',
            'unit_id' => 'required',
            'remarks' => 'required',
        ];
        return $rules;
    }

    public function authorize(){
        return true;
    }
}