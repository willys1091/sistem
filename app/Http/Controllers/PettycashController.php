<?php

namespace App\Http\Controllers;

use App\DataTables\PettycashesDataTable;
use App\DataTables\PettycashesDetailDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Pettycashes\StorePettycash;
use App\Http\Requests\Pettycashes\StorePettycashDetail;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Approval;
use App\Models\ApprovalAct;
use App\Models\ApprovalState;
use App\Models\Pettycash;
use App\Models\PettycashDetail;
use App\Models\PettycashAct;
use App\Models\PettycashApproval;
use App\Models\ExpensesCategory;
use App\Models\ExpensesCategoryRole;
use App\Models\PettycashesCategoryDetail;
use App\Models\Project;
use App\Models\ClientDetails;
use App\Models\RoleUser;
use App\Models\User;
use App\Traits\General;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PettycashController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.pettycashes';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('pettycashes', $this->user->modules));
            return $next($request);
        });
    }

    public function index(PettycashesDataTable $dataTable){
        $viewPermission = user()->permission('view_pettycashes');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->employees = User::allEmployees(null, true);
            $this->projects = Project::allProjects();
            $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        }
        return $dataTable->render('pettycashes.index', $this->data);
    }

    private function detail($id){
        $dataTable = new PettycashesDetailDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'pettycashes.ajax.detail';
        return $dataTable->render('pettycashes.show', $this->data);
    }

    public function changeStatus(Request $request){
        abort_403(user()->permission('approve_pettycashes') != 'all');

        $pettycashId = $request->pettycashId;
        $status = $request->status;
        $pettycash = Pettycash::findOrFail($pettycashId);
        $pettycash->status = $status;
        $pettycash->save();
        return Reply::success(__('messages.updateSuccess'));
    }

    public function show($id){
        $this->viewPermission = user()->permission('view_pettycashes');
        $viewProjectPermission = user()->permission('view_project_pettycashes');
        $this->editPettycashPermission = user()->permission('edit_pettycashes');
        $this->deletePettycashPermission = user()->permission('delete_pettycashes');

        $this->pettycash = Pettycash::with(['user', 'project', 'category'])->findOrFail($id)->withCustomFields();

        // abort_403(!($this->viewPermission == 'all'|| ($this->viewPermission == 'added' && $this->pettycash->added_by == user()->id)|| ($viewProjectPermission == 'owned' || $this->pettycash->user_id == user()->id)));

        $this->pageTitle = $this->pettycash->item_name;

        if ($this->pettycash->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->pettycash->getCustomFieldGroupsWithFields()->fields;
        }

        $tab = request('tab');

        switch ($tab) {
            case 'detail':
                return $this->detail($id);
                break;
            default:
                $this->view = 'pettycashes.ajax.overview';
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('pettycashes.show', $this->data);
    }

    public function create(){
        $this->addPermission = user()->permission('add_pettycashes');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->clients = User::allClients(null, false, ($this->addPermission == 'all' ? 'all' : null));
        $this->linkPettycashPermission = user()->permission('link_pettycash_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if($this->viewBankAccountPermission == 'added'){
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        // Get only current login employee projects
        if ($this->addPermission == 'added') {
            $this->projects = Project::where('added_by', user()->id)->orWhereHas('projectMembers', function ($query) {
                $query->where('user_id', user()->id);
            })->get();

            $this->clients = ClientDetails::all();
        } else {
            $this->projects = Project::all();
            $this->clients = ClientDetails::all();
        }

        $this->pageTitle = __('modules.pettycashes.addPettycash');
        $this->projectId = request('project_id') ? request('project_id') : null;
       
        if (!is_null($this->projectId)) {
            $this->project = Project::with('projectMembers')->where('id', $this->projectId)->first();
            $this->projectName = $this->project->project_name;
            $this->employees = $this->project->projectMembers;
        } else {
            $this->employees = User::allEmployees(null, true);
        }

        $pettycash = new Pettycash();

        if ($pettycash->getCustomFieldGroupsWithFields()) {
            $this->fields = $pettycash->getCustomFieldGroupsWithFields()->fields;
        }

        $this->view = 'pettycashes.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }
        return view('pettycashes.show', $this->data);
    }

    public function store(StorePettycash $request){
        $userRole = session('user_roles');
        
        $isdetail = ExpensesCategory::whereId($request->category_id)->value('is_detail');
        
        $pettycash = new Pettycash();
        $pettycash->code = $this->generateCode();
        company()?$pettycash->company_id = company()->id : '';
        $pettycash->item_name = $request->item_name;
        $pettycash->purchase_date = companyToYmd($request->purchase_date);
        $pettycash->purchase_from = $request->purchase_from;
        $pettycash->price = round($request->price, 2);
        $pettycash->currency_id = $request->currency_id;
        $pettycash->category_id = $request->category_id;
        $pettycash->user_id = $request->user_id;
        $pettycash->default_currency_id = company()->currency_id;
        $pettycash->exchange_rate = $request->exchange_rate;
        $pettycash->client_id = $request->client_id;
        $pettycash->urgency = $request->urgency;
        $pettycash->payment_type = $request->payment_type;
        $pettycash->payee = $request->payee;
        $pettycash->bank_account = $request->bank_account;
        $pettycash->bank_name = $request->bank_name;
        $pettycash->description = trim_editor($request->description);
        $pettycash->added_by = $request->user_id;
        $pettycash->is_detail = $isdetail;

        if ($userRole[0] == 'admin') {
            $pettycash->status = 'approved';
            $pettycash->approver_id = user()->id;
        }

        if ($request->has('status')) {
            $pettycash->status = $request->status;
            $pettycash->approver_id = user()->id;
        }

        if ($request->has('project_id') && $request->project_id != '0') {
            $pettycash->project_id = $request->project_id;
        }

        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Pettycash::FILE_PATH);
            $pettycash->bill = $filename;
        }
        $pettycash->bank_account_id = $request->bank_account_id;
        $pettycash->save();
        
        if($isdetail <>'1'){
            $this->generateApproval($pettycash->id,0,0,round($request->price, 2));
        }

        if ($request->custom_fields_data) {
            $pettycash->updateCustomFieldData($request->custom_fields_data);
        }
        $redirectUrl = urldecode($request->redirect_url);
        if ($redirectUrl == '') {
                $redirectUrl = $isdetail =='1'?route('pettycashes.show', [$pettycash->id,'tab=detail']) : route('pettycashes.index');
        }
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
    }

    public function edit($id){
        $this->pettycash = Pettycash::findOrFail($id)->withCustomFields();
        $this->editPermission = user()->permission('edit_pettycashes');

        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->pettycash->added_by == user()->id)));

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->employees = User::allEmployees();
        $this->pageTitle = __('modules.pettycashes.updatePettycash');
        $this->linkPettycashPermission = user()->permission('link_pettycash_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->pettycash->currency_id);

        if($this->viewBankAccountPermission == 'added'){
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $userId = $this->pettycash->user_id;

        if (!is_null($userId)) {
            $this->projects = Project::with('members')->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();
            $this->clients = ClientDetails::all();
        }else{
            $this->projects = Project::get();
            $this->clients = ClientDetails::all();
        }

        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        $pettycash = new Pettycash();

        if ($pettycash->getCustomFieldGroupsWithFields()) {
            $this->fields = $pettycash->getCustomFieldGroupsWithFields()->fields;
        }

        $this->view = 'pettycashes.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('pettycashes.show', $this->data);
    }

    public function update(StorePettycash $request, $id){
        $pettycash = Pettycash::findOrFail($id);
        $pettycash->item_name = $request->item_name;
        $pettycash->purchase_date = companyToYmd($request->purchase_date);
        $pettycash->purchase_from = $request->purchase_from;
        $request->category_id<>'7' ? $pettycash->price = round($request->price, 2) : '';
        $pettycash->currency_id = $request->currency_id;
        $pettycash->user_id = $request->user_id;
        $pettycash->category_id = $request->category_id;
        $pettycash->default_currency_id = company()->currency_id;
        $pettycash->exchange_rate = $request->exchange_rate;
        $pettycash->client_id = $request->client_id;
        $pettycash->urgency = $request->urgency;
        $pettycash->payment_type = $request->payment_type;
        $pettycash->payee = $request->payee;
        $pettycash->bank_account = $request->bank_account;
        $pettycash->bank_name = $request->bank_name;
        $pettycash->description = trim_editor($request->description);

        $pettycash->project_id = ($request->project_id > 0) ? $request->project_id : null;


        if ($request->bill_delete == 'yes') {
            Files::deleteFile($pettycash->bill, Pettycash::FILE_PATH);
            $pettycash->bill = null;
        }

        if ($request->hasFile('bill')) {
            Files::deleteFile($pettycash->bill, Pettycash::FILE_PATH);

            $filename = Files::uploadLocalOrS3($request->bill, Pettycash::FILE_PATH);
            $pettycash->bill = $filename;
        }

        if ($request->has('status')) {
            $pettycash->status = $request->status;
        }

        $pettycash->bank_account_id = $request->bank_account_id;
        $pettycash->save();

        $this->generateApproval($id,0,$pettycash->is_detail=='1'?1:0,round($request->price, 2));
        
        // To add custom fields data
        if ($request->custom_fields_data) {
            $pettycash->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('pettycashes.index')]);
    }

    public function destroy($id){
        $this->pettycash = Pettycash::findOrFail($id);
        $this->deletePermission = user()->permission('delete_pettycashes');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->pettycash->added_by == user()->id)));

        Pettycash::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function detailCreate(){
        $this->pettycashId = request('id');
        $this->pettycash = Pettycash::findOrFail($this->pettycashId);
        $this->categories = PettycashesCategoryDetail::orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('pettycashes.ajax.createDetail', $this->data);
    }

    public function detailStore(StorePettycashDetail $request){
        $detail = new PettycashDetail();
        $detail->header_id = $request->pettycash_id;
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        $detail->save();

        $amt = PettycashDetail::whereHeader_id($request->pettycash_id)->sum('amount');
        Pettycash::whereId($request->pettycash_id)->update(['price' => $amt ]);
        $exp = Pettycash::findorfail($request->pettycash_id);
        $this->generateApproval($request->pettycash_id,0,$exp->is_detail=='1'?1:0,round($amt, 2));
        
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('pettycashes.show', [$request->pettycash_id,'tab=detail'])]);
    }

    public function detailedit($id){
        //$this->pettycash = Pettycash::findOrFail($this->pettycashId);
        $this->detail = PettycashDetail::findOrFail($id);
        $this->categories = PettycashesCategoryDetail::orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('pettycashes.ajax.editDetail', $this->data);
    }

    public function detailupdate(StorePettycashDetail $request,$id){
        $detail = PettycashDetail::findOrFail($id);
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        $detail->save();

        $amt = PettycashDetail::whereHeader_id($detail->header_id)->sum('amount');
        Pettycash::whereId($detail->header_id)->update(['price' => $amt ]);
        $exp = Pettycash::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,0,$exp->is_detail=='1'?1:0,round($amt, 2));
        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('pettycashes.show', [$detail->header_id,'tab=detail'])]);
    }

    public function detailDelete($id){
        $detail = PettycashDetail::whereId($id)->first();
        PettycashDetail::destroy($id);
        $amt = PettycashDetail::whereHeader_id($detail->header_id)->sum('amount');
        Pettycash::whereId($detail->header_id)->update(['price' => $amt ]);
        $exp = Pettycash::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,0,$exp->is_detail=='1'?1:0,round($amt, 2));
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function applyQuickAction(Request $request){
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);
                return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            $this->changeBulkStatus($request);
                return Reply::success(__('messages.updateSuccess'));
        default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    public function approvalList($id){
        $total = PettycashApproval::whereHeader_id($id)->count();
        if($total==1){
            $this->approval = PettycashApproval::whereHeader_id($id)->get();
        }elseif($total>1){
            $this->approval = PettycashApproval::whereHeader_id($id)->whereNotIn('id',PettycashApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        }
        return view('pettycashes.approval', $this->data);
    }

    public function checkApproval(Request $request){
        $act = PettycashAct::findorfail($request->actid);
        if(PettycashApproval::whereId($act->approval_id)->value('user_id') == user()->id){
            return Reply::success('Have Access to Respond');
        }else{
            return Reply::error('You Don\'t Have Access to Respond');
        }
    }

    public function checkCategoryDetail(Request $request){
        $isdetail = ExpensesCategory::whereId($request->category_id)->value('is_detail');
        return Reply::success($isdetail);
    }

    public function response($id,$actid){
        $this->header = Pettycash::findorfail($id);
        $this->act = $act = PettycashAct::findorfail($actid);
        return view('pettycashes.response', $this->data);
    }

    public function responseAction(Request $request){
        $act = PettycashAct::findorfail($request->act_id);
        PettycashApproval::whereId($act->approval_id)->update(['status'=>$act->apv_status,'remarks'=>$request->description,'approval_date'=>date('Y-m-d H:i:s')]);
        Pettycash::whereId($request->header_id)->update(['status'=>$act->status,'state_id'=>$act->next_state]);
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('pettycashes.index')]);
    }

    public function copy(Request $request){
        $header = Pettycash::findorfail($request->pettycashId);
       
        $head = new Pettycash;
        $head->code = $this->generateCode();
        $head->company_id = $header->company_id;
        $head->item_name = $header->item_name;
        $head->purchase_date = $header->purchase_date;
        $head->purchase_from = $header->purchase_from;
        $head->price = $header->price;
        $head->currency_id = $header->currency_id;
        $head->default_currency_id = $header->default_currency_id;
        $head->exchange_rate = $header->exchange_rate;
        $head->client_id = $header->client_id;
        $head->project_id = $header->project_id;
        $head->bill = $header->bill;
        $head->user_id = $header->user_id;
        $head->urgency = $header->urgency;
        $head->payment_type = $header->payment_type;
        $head->status = 'draft';
        $head->state_id = '1';
        $head->can_claim = $header->can_claim;
        $head->category_id = $header->category_id;
        $head->pettycashes_recurring_id = $header->pettycashes_recurring_id;
        $head->created_by = $header->created_by;
        $head->description = $header->description;
        $head->added_by = $header->added_by;
        $head->last_updated_by = $header->last_updated_by;
        $head->approver_id = $header->approver_id;
        $head->payee = $header->payee;
        $head->bank_account_id = $header->bank_account_id;
        $head->bank_account = $header->bank_account;
        $head->bank_name = $header->bank_name;
        $head->is_detail = $header->is_detail;
        $head->copy_reff = $request->pettycashId;
        $head->save();

        $this->generateApproval($head->id,0,$header->is_detail=='1'?1:0,round($header->price, 2));

        if($header->is_detail=='1'){
            $detail = PettycashDetail::whereHeader_id($request->pettycashId)->get();
            foreach($detail as $d){
                $det = new PettycashDetail;
                $det->header_id = $head->id;
                $det->category_id = $d->category_id;
                $det->remarks = $d->remarks;
                $det->amount = $d->amount;
                $det->estdate = $d->estdate;
                $det->created_by = Session('id');
                $det->created_at = date('Y-m-d H:i:s');
                $det->save();
            }
        }
        Pettycash::whereId($request->pettycashId)->update(['is_copied' => 1]);
        return Reply::success('Copied Successfull');
    }

    public function download($id){
        // App::setLocale($this->invoiceSetting->locale ?? 'en');
        // Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForDownload($id){
        $this->header = Pettycash::findorfail($id);
        $this->detail = PettycashDetail::whereHeader_id($id)->get();
        $this->approval = PettycashApproval::whereHeader_id($id)->whereNotIn('id',PettycashApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        $this->inword = $this->numerator($this->header->price);

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('pettycashes.pdf.pettycash', $this->data);
        $filename = date('Y-m-d H:i:s');

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    protected function deleteRecords($request){
        abort_403(user()->permission('delete_employees') != 'all');

        // Did this to call observer
        foreach (Pettycash::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get() as $delete) {
            $delete->delete();
        }
    }

    protected function changeBulkStatus($request){
        abort_403(user()->permission('edit_employees') != 'all');

        $pettycashes = Pettycash::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get();
        $pettycashes->each(function ($pettycash) use ($request) {
            $pettycash->status = $request->status;
            $pettycash->save();
        });
    }

    protected function getEmployeeProjects(Request $request){
        // Get employee category
        if (!is_null($request->userId)) {
            $categories = ExpensesCategory::with('roles')->whereHas('roles', function($q) use ($request) {
                $user = User::findOrFail($request->userId);

                $roleId = (count($user->role) > 1) ? $user->role[1]->role_id : $user->role[0]->role_id;
                $q->where('role_id', $roleId);
            })->get();

        }
        else {
            $categories = ExpensesCategory::get();
        }

        if($categories) {
            foreach ($categories as $category) {
                $selected = $category->id == $request->categoryId ? 'selected' : '';
                $categories .= '<option value="' . $category->id . '"'.$selected.'>' . $category->category_name . '</option>';
            }
        }

        // Get employee project
        if (!is_null($request->userId)) {
            $projects = Project::with('members')->whereHas('members', function ($q) use ($request) {
                $q->where('user_id', $request->userId);
            })->get();
        }
        else if(user()->permission('add_pettycashes') == 'all' && is_null($request->userId))
        {
            $projects = [];
        }
        else {
            $projects = Project::get();
        }

        $data = null;

        if ($projects) {
            foreach ($projects as $project) {
                $data .= '<option data-currency-id="'. $project->currency_id .'" value="' . $project->id . '">' . $project->project_name . '</option>';
            }
        }


        return Reply::dataOnly(['status' => 'success', 'data' => $data, 'category' => $categories]);
    }

    protected function getCategoryEmployee(Request $request){
        $pettycashCategory = ExpensesCategoryRole::where('expenses_category_id', $request->categoryId)->get();
        $roleId = [];
        $managers = [];
        $employees = [];

        foreach($pettycashCategory as $category) {
            array_push($roleId, $category->role_id);
        }

        if (count($roleId ) == 1 && $roleId != null) {
            $users = User::whereHas(
                'role', function($q)  use ($roleId) {
                    $q->whereIn('role_id', $roleId);
                }
            )->get();

            foreach ($users as $user) {
                ($user->hasRole('Manager')) ? array_push($managers, $user) : array_push($employees, $user);
            }
        }
        else {
            $employees = User::allEmployees(null, true);
        }

        $data = null;

        if ($employees) {
            foreach ($employees as $employee) {

                $data .= '<option ';

                $selected = $employee->id == $request->userId ? 'selected' : '';
                $itsYou = $employee->id == user()->id ? "<span class='ml-2 badge badge-secondary pr-1'>". __('app.itsYou') .'</span>' : '';

                $data .= 'data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=\'' . $employee->image_url . '\' ></div> '.$employee->name.$itsYou.'"
                value="' . $employee->id . '"'.$selected.'>'.$employee->name.'</option>';

            }
        }
        else {
            foreach ($managers as $manager) {
                $data .= '<option ';

                $selected = $manager->id == $request->userId ? 'selected' : '';
                $itsYou = $manager->id == user()->id ? "<span class='ml-2 badge badge-secondary pr-1'>" . __('app.itsYou') . '</span>' : '';
                $data .= 'data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=\'' . $manager->image_url . '\' ></div> '.$manager->name.'"
                value="' . $manager->id . '"'.$selected.'>'.$manager->name.$itsYou.'</option>';
            }
        }
        return Reply::dataOnly(['status' => 'success', 'employees' => $data]);
    }

    private function generateCode(){
        $last = Pettycash::whereRaw('year(created_at)="'.date('Y').'"')->whereRaw('month(created_at)="'.date('m').'"')->latest('id');
        if($last->count() > 0){
            $data = $last->first();
            return "PC".date('ymd').str_pad(((int)substr($data->code,9,4) +1), 4, '0', STR_PAD_LEFT);
        }else{
            return "PC".date('ymd')."0001";
        }
    }

    protected function generateApproval($id,$urgency,$employment,$price){
        $apvid = Approval::whereNameAndCompany_idAndIs_urgencyAndIs_employment('pettycash',user()->company_id,$urgency,$employment)
                ->where('limit_min', '<=', $price)
                ->Where(function ($query) use ($price) {
                    $query->where('limit_max', '0')
                    ->orWhere('limit_max', '>=', $price);
                })->value('id');
        
        $expapv = Pettycash::whereId($id)->value('approval_id');
        if($apvid <> $expapv){
            PettycashApproval::Where('header_id',$id)->delete();
            Pettycash::whereId($id)->update(['approval_id' => $apvid ]);
            $state = ApprovalState::whereApproval_id($apvid)->get();

            foreach($state as $s){
                if(!filter_var($s->users, FILTER_VALIDATE_INT)){
                    $user = $this->getUserApproval($s->users,user()->id);
                }else{
                    $user = $s->users;
                }
                $apv = new PettycashApproval;
                $apv->header_id = $id;
                $apv->state_id = $s->state_id;
                $apv->user_id = $user;
                $apv->save();

                $apvact = ApprovalAct::whereState_idAndApproval_id($s->state_id,$apvid)->get();

                foreach($apvact as $aa){
                    $act = new PettycashAct;
                    $act->approval_id = $apv->id;
                    $act->name = $aa->name;
                    $act->next_state = $aa->next_state;
                    $act->status = $aa->status;
                    $act->apv_status = $aa->apv_status;
                    $act->icon = $aa->icon;
                    $act->color = $aa->color;
                    $act->save();
                }
            }
        }
    }
}