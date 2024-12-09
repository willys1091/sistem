<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventNote extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        return [
            // 'note' => 'required'
        ];
    }
}