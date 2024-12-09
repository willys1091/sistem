<?php

namespace App\Http\Requests\Pettycashes;

use App\Http\Requests\CoreRequest;

class StorePettycashCategory extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        return [
            'category_name' => 'sometimes|required|unique:pettycashes_category,category_name,null,id,company_id,' . company()->id
        ];
    }
}