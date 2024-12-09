<?php

namespace Modules\Purchase\Http\Requests\PurchaseRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest{   
    public function rules(){
        $setting = company();
        
        $rules = [
            'request_date' => 'required|date_format:"' . $setting->date_format . '"',
            'estimation_date' => 'required|date_format:"' . $setting->date_format . '"',
        ];
        return $rules;
    }

    public function authorize(){
        return true;
    }
}