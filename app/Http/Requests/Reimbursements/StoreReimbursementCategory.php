<?php

namespace App\Http\Requests\Reimbursements;

use App\Http\Requests\CoreRequest;

class StoreReimbursementCategory extends CoreRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        return [
            'category_name' => 'sometimes|required|unique:reimbursements_category,category_name,null,id,company_id,' . company()->id
        ];
    }
}