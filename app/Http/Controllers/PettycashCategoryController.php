<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\Pettycashes\StorePettycashCategory;
use App\Models\BaseModel;
use App\Models\PettycashesCategory;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PettycashCategoryController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.pettycashes';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('pettycashes', $this->user->modules));

            return $next($request);
        });
    }

    public function create()
    {
        $this->categories = $this->getCategoryByCurrentRole();
        $this->roles = Role::where('name', '<>', 'admin')->where('name', '<>', 'client')->get();

        return view('pettycashes.category.create', $this->data);
    }

    public function store(StorePettycashCategory $request)
    {
        $category = new PettycashesCategory();
        $category->category_name = strip_tags($request->category_name);
        $category->save();

        $roles = $request->role;

        $categories = PettycashesCategory::with(['roles', 'roles.role'])->get();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $options]);
    }

    public function update(StorePettycashCategory $request, $id)
    {
        $group = PettycashesCategory::findOrFail($id);
        $category = $request->category_name;

      

        if ($category != '') {
            $group->category_name = $request->category_name;
        }

        $group->save();

        $categories = PettycashesCategory::all();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $options]);
    }

    public function destroy($id)
    {
        PettycashesCategory::destroy($id);

        $categories = PettycashesCategory::all();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.deleteSuccess'), ['data' => $options]);
    }
}