<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementsCategoryRole;
use App\Helper\Reply;
use App\Http\Requests\Reimbursements\StoreReimbursementCategory;
use App\Models\BaseModel;
use App\Models\ReimbursementsCategory;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ReimbursementCategoryController extends AccountBaseController{
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.reimbursements';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('reimbursements', $this->user->modules));

            return $next($request);
        });
    }

    public function create(){
        $this->categories = $this->getCategoryByCurrentRole();
        $this->roles = Role::where('name', '<>', 'admin')->where('name', '<>', 'client')->get();

        return view('reimbursements.category.create', $this->data);
    }

    public function store(StoreReimbursementCategory $request){
        $category = new ReimbursementsCategory();
        $category->category_name = strip_tags($request->category_name);
        $category->save();

        $roles = $request->role;

        if ($request->role && count($roles) > 0) // If selected role id.
        {
            ReimbursementsCategoryRole::where('reimbursements_category_id', $category->id)->delete();

            foreach ($roles as $role) {
                $expansesCategoryRoles = new ReimbursementsCategoryRole();
                $expansesCategoryRoles->reimbursements_category_id = $category->id;
                $expansesCategoryRoles->role_id = $role;
                $expansesCategoryRoles->save();
            }
        }
        else {
            // If not selected role id select all roles default.
            $rolesData = Role::where('name', '<>', 'admin')->where('name', '<>', 'client')->get();

            foreach ($rolesData as $roleData) {
                $expansesCategoryRoles = new ReimbursementsCategoryRole();
                $expansesCategoryRoles->reimbursements_category_id = $category->id;
                $expansesCategoryRoles->role_id = $roleData->id;
                $expansesCategoryRoles->save();
            }
        }

        $categories = ReimbursementsCategory::with(['roles', 'roles.role'])->get();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $options]);
    }

    public function update(StoreReimbursementCategory $request, $id){
        $group = ReimbursementsCategory::findOrFail($id);
        $category = $request->category_name;

        if ($request->has('role_update')) // If selected role id.
        {

            $roles = $request->roles;

            if ((is_array($roles) && count($roles) == 0) || is_null($roles)) {
                return Reply::error(__('messages.roleNotAssigned'));
            }

            ReimbursementsCategoryRole::where('reimbursements_category_id', $group->id)->delete();

            foreach ($roles as $role) {
                $expansesCategoryRoles = new ReimbursementsCategoryRole();
                $expansesCategoryRoles->reimbursements_category_id = $group->id;
                $expansesCategoryRoles->role_id = $role;
                $expansesCategoryRoles->save();
            }
        }

        if ($category != '') {
            $group->category_name = $request->category_name;
        }

        $group->save();

        $categories = ReimbursementsCategory::all();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $options]);
    }

    public function destroy($id){
        ReimbursementsCategory::destroy($id);

        $categories = ReimbursementsCategory::all();
        $options = BaseModel::options($categories, null, 'category_name');

        return Reply::successWithData(__('messages.deleteSuccess'), ['data' => $options]);
    }
}