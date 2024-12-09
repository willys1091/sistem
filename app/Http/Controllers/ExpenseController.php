<?php

namespace App\Http\Controllers;

use App\DataTables\ExpensesDataTable;
use App\DataTables\ExpensesDetailDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Expenses\StoreExpense;
use App\Http\Requests\Expenses\StoreExpenseDetail;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Approval;
use App\Models\ApprovalAct;
use App\Models\ApprovalState;
use App\Models\Expense;
use App\Models\ExpenseDetail;
use App\Models\ExpenseAct;
use App\Models\ExpenseApproval;
use App\Models\ExpensesCategory;
use App\Models\ExpensesCategoryRole;
use App\Models\ExpensesCategoryDetail;
use App\Models\Project;
use App\Models\ClientDetails;
use App\Models\RoleUser;
use App\Models\User;
use App\Traits\General;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.expenses';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('expenses', $this->user->modules));
            return $next($request);
        });
    }

    public function index(ExpensesDataTable $dataTable){
        $viewPermission = user()->permission('view_expenses');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->employees = User::allEmployees(null, true);
            $this->projects = Project::allProjects();
            $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
            $this->approvals = ApprovalState::select('approval_state.users','users.name')->join('approval','approval.id','approval_state.approval_id')->join('users','users.id','approval_state.users')
                                ->where('approval_state.state_id','>','2')->where('approval.name','expense')->where('approval.company_id',user()->company_id)->where('approval_state.users','<>','author')->groupby('approval_state.users')->get();
        }
        return $dataTable->render('expenses.index', $this->data);
    }

    private function detail($id){
        $dataTable = new ExpensesDetailDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'expenses.ajax.detail';
        return $dataTable->render('expenses.show', $this->data);
    }

    public function changeStatus(Request $request){
        abort_403(user()->permission('approve_expenses') != 'all');

        $expenseId = $request->expenseId;
        $status = $request->status;
        $expense = Expense::findOrFail($expenseId);
        $expense->status = $status;
        $expense->save();
        return Reply::success(__('messages.updateSuccess'));
    }

    public function show($id){
        $this->viewPermission = user()->permission('view_expenses');
        $viewProjectPermission = user()->permission('view_project_expenses');
        $this->editExpensePermission = user()->permission('edit_expenses');
        $this->deleteExpensePermission = user()->permission('delete_expenses');

        $this->expense = Expense::with(['user', 'project', 'category', 'transactions' => function($q){
            $q->orderByDesc('id')->limit(1);
        }, 'transactions.bankAccount'])->findOrFail($id)->withCustomFields();

        // abort_403(!($this->viewPermission == 'all'|| ($this->viewPermission == 'added' && $this->expense->added_by == user()->id)|| ($viewProjectPermission == 'owned' || $this->expense->user_id == user()->id)));

        $this->pageTitle = $this->expense->item_name;

        if ($this->expense->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->expense->getCustomFieldGroupsWithFields()->fields;
        }

        $tab = request('tab');

        switch ($tab) {
            case 'detail':
                return $this->detail($id);
                break;
            default:
                $this->view = 'expenses.ajax.overview';
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('expenses.show', $this->data);
    }

    public function create(){
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->clients = User::allClients(null, false, ($this->addPermission == 'all' ? 'all' : null));
        $this->linkExpensePermission = user()->permission('link_expense_bank_account');
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

        $this->pageTitle = __('modules.expenses.addExpense');
        $this->projectId = request('project_id') ? request('project_id') : null;
       
        if (!is_null($this->projectId)) {
            $this->project = Project::with('projectMembers')->where('id', $this->projectId)->first();
            $this->projectName = $this->project->project_name;
            $this->employees = $this->project->projectMembers;
        } else {
            $this->employees = User::allEmployees(null, true);
        }

        $expense = new Expense();

        if ($expense->getCustomFieldGroupsWithFields()) {
            $this->fields = $expense->getCustomFieldGroupsWithFields()->fields;
        }

        $this->view = 'expenses.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }
        return view('expenses.show', $this->data);
    }

    public function store(StoreExpense $request){
        $userRole = session('user_roles');
        
        $isdetail = ExpensesCategory::whereId($request->category_id)->value('is_detail');
        
        $expense = new Expense();
        $expense->code = $this->generateCode($request->urgency=='normal'?'N':'U',$isdetail=='1'?'K':'N');
        $expense->item_name = $request->item_name;
        $expense->purchase_date = companyToYmd($request->purchase_date);
        $expense->purchase_from = $request->purchase_from;
        $expense->price = round($request->price, 2);
        $expense->currency_id = $request->currency_id;
        $expense->category_id = $request->category_id;
        $expense->user_id = $request->user_id;
        $expense->default_currency_id = company()->currency_id;
        $expense->exchange_rate = $request->exchange_rate;
        $expense->client_id = $request->client_id;
        $expense->urgency = $request->urgency;
        $expense->payment_type = $request->payment_type;
        $expense->payee = $request->payee;
        $expense->bank_account = $request->bank_account;
        $expense->bank_name = $request->bank_name;
        $expense->description = trim_editor($request->description);
        $expense->is_detail = $isdetail;

        if ($userRole[0] == 'admin') {
            $expense->status = 'approved';
            $expense->approver_id = user()->id;
        }

        if ($request->has('status')) {
            $expense->status = $request->status;
            $expense->approver_id = user()->id;
        }

        if ($request->has('project_id') && $request->project_id != '0') {
            $expense->project_id = $request->project_id;
        }

        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Expense::FILE_PATH);
            $expense->bill = $filename;
        }
        $expense->bank_account_id = $request->bank_account_id;
        $expense->save();
        
        if($isdetail <>'1'){
            $this->generateApproval($expense->id,$request->urgency=='normal'?0:1,0,round($request->price, 2));
        }

        if ($request->custom_fields_data) {
            $expense->updateCustomFieldData($request->custom_fields_data);
        }
        $redirectUrl = urldecode($request->redirect_url);
        if ($redirectUrl == '') {
                $redirectUrl = $isdetail =='1'?route('expenses.show', [$expense->id,'tab=detail']) : route('expenses.index');
        }
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
    }

    public function edit($id){
        $this->expense = Expense::findOrFail($id)->withCustomFields();
        $this->editPermission = user()->permission('edit_expenses');

        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->expense->added_by == user()->id)));

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->employees = User::allEmployees();
        $this->pageTitle = __('modules.expenses.updateExpense');
        $this->linkExpensePermission = user()->permission('link_expense_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->expense->currency_id);

        if($this->viewBankAccountPermission == 'added'){
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $userId = $this->expense->user_id;

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

        $expense = new Expense();

        if ($expense->getCustomFieldGroupsWithFields()) {
            $this->fields = $expense->getCustomFieldGroupsWithFields()->fields;
        }

        $this->view = 'expenses.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('expenses.show', $this->data);
    }

    public function update(Request $request, $id){
        $expense = Expense::findOrFail($id);
        $expense->item_name = $request->item_name;
        $expense->purchase_date = companyToYmd($request->purchase_date);
        $expense->purchase_from = $request->purchase_from;
        $expense->is_detail==0 ? $expense->price = round($request->price, 2) : '';
        $expense->currency_id = $request->currency_id;
        $expense->user_id = $request->user_id;
        $expense->default_currency_id = company()->currency_id;
        $expense->exchange_rate = $request->exchange_rate;
        $expense->client_id = $request->client_id;
        $expense->payment_type = $request->payment_type;
        $expense->payee = $request->payee;
        $expense->bank_account = $request->bank_account;
        $expense->bank_name = $request->bank_name;
        $expense->urgency = $request->urgency;
        $expense->description = trim_editor($request->description);

        $expense->project_id = ($request->project_id > 0) ? $request->project_id : null;


        if ($request->bill_delete == 'yes') {
            Files::deleteFile($expense->bill, Expense::FILE_PATH);
            $expense->bill = null;
        }

        if ($request->hasFile('bill')) {
            Files::deleteFile($expense->bill, Expense::FILE_PATH);

            $filename = Files::uploadLocalOrS3($request->bill, Expense::FILE_PATH);
            $expense->bill = $filename;
        }

        if ($request->has('status')) {
            $expense->status = $request->status;
        }

        $expense->bank_account_id = $request->bank_account_id;
        $expense->save();

        $this->generateApproval($id,$request->urgency=='normal'?0:1,$expense->is_detail=='1'?1:0,round($request->price, 2));
        
        // To add custom fields data
        if ($request->custom_fields_data) {
            $expense->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('expenses.index')]);
    }

    public function destroy($id){
        $this->expense = Expense::findOrFail($id);
        $this->deletePermission = user()->permission('delete_expenses');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->expense->added_by == user()->id)));

        Expense::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function detailCreate(){
        $this->expenseId = request('id');
        $this->expense = Expense::findOrFail($this->expenseId);
        $this->categories = ExpensesCategoryDetail::whereType('k')->orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('expenses.ajax.createDetail', $this->data);
    }

    public function detailStore(StoreExpenseDetail $request){
        $detail = new ExpenseDetail();
        $detail->header_id = $request->expense_id;
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        $detail->save();

        $amt = ExpenseDetail::whereHeader_id($request->expense_id)->sum('amount');
        Expense::whereId($request->expense_id)->update(['price' => $amt ]);
        $exp = Expense::findorfail($request->expense_id);
        $this->generateApproval($request->expense_id,$exp->urgency=='normal'?0:1,$exp->is_detail=='1'?1:0,round($amt, 2));
        
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('expenses.show', [$request->expense_id,'tab=detail'])]);
    }

    public function detailedit($id){
        //$this->expense = Expense::findOrFail($this->expenseId);
        $this->detail = ExpenseDetail::findOrFail($id);
        $this->categories = ExpensesCategoryDetail::whereType('k')->orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('expenses.ajax.editDetail', $this->data);
    }

    public function detailupdate(StoreExpenseDetail $request,$id){
        $detail = ExpenseDetail::findOrFail($id);
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        $detail->save();

        $amt = ExpenseDetail::whereHeader_id($detail->header_id)->sum('amount');
        Expense::whereId($detail->header_id)->update(['price' => $amt ]);
        $exp = Expense::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,$exp->urgency=='normal'?0:1,$exp->is_detail=='1'?1:0,round($amt, 2));
        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('expenses.show', [$detail->header_id,'tab=detail'])]);
    }

    public function detailDelete($id){
        $detail = ExpenseDetail::whereId($id)->first();
        ExpenseDetail::destroy($id);
        $amt = ExpenseDetail::whereHeader_id($detail->header_id)->sum('amount');
        Expense::whereId($detail->header_id)->update(['price' => $amt ]);
        $exp = Expense::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,$exp->urgency=='normal'?0:1,$exp->is_detail=='1'?1:0,round($amt, 2));
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
        $total = ExpenseApproval::whereHeader_id($id)->count();
        if($total==1){
            $this->approval = ExpenseApproval::whereHeader_id($id)->get();
        }elseif($total>1){
            $this->approval = ExpenseApproval::whereHeader_id($id)->whereNotIn('id',ExpenseApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        }
        return view('expenses.approval', $this->data);
    }

    public function checkApproval(Request $request){
        $act = ExpenseAct::findorfail($request->actid);
        if(ExpenseApproval::whereId($act->approval_id)->value('user_id') == user()->id){
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
        $this->header = Expense::findorfail($id);
        $this->act = $act = ExpenseAct::findorfail($actid);
        return view('expenses.response', $this->data);
    }

    public function responseAction(Request $request){
        $act = ExpenseAct::findorfail($request->act_id);
        ExpenseApproval::whereId($act->approval_id)->update(['status'=>$act->apv_status,'remarks'=>$request->description,'approval_date'=>date('Y-m-d H:i:s')]);
        Expense::whereId($request->header_id)->update(['status'=>$act->status,'state_id'=>$act->next_state]);
        if($act->name =='Tax Approve'){
            $expense = Expense::findorfail($request->header_id);
            $expense->procurement = $request->procurement;
            $expense->subject = $request->subject;
            $expense->tax_no = $request->taxNo;
            $expense->type_tax_mount = $request->typetaxAmount;
            $expense->tax_amount_basic = $request->taxAmountBasic;
            $expense->tax_amount = $request->taxAmount;
            $expense->type_tax_income1 = $request->typePph1;
            $expense->tax_income1_basic = $request->pph1Basic;
            $expense->tax_income1 = $request->pph1;
            $expense->type_tax_income2 = $request->typePph2;
            $expense->tax_income2_basic = $request->pph2basic;
            $expense->tax_income2 = $request->pph2;
            $expense->type_tax_vat = $request->typePpn;
            $expense->tax_vat_basic = $request->ppnBasic;
            $expense->tax_vat = $request->ppn;
            $expense->tax_total_basic = $request->taxTotalBasic;
            $expense->tax_total = $request->taxTotal;
            $expense->save();
        }
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('expenses.index')]);
    }

    public function copy(Request $request){
        $header = Expense::findorfail($request->expenseId);
       
        $head = new Expense;
        $head->code = $this->generateCode($header->urgency=='normal'?'N':'U',$header->is_detail=='1'?'K':'N');
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
        $head->expenses_recurring_id = $header->expenses_recurring_id;
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
        $head->copy_reff = $request->expenseId;
        $head->save();

        $this->generateApproval($head->id,$header->urgency=='normal'?0:1,$header->is_detail=='1'?1:0,round($header->price, 2));

        if($header->is_detail=='1'){
            $detail = ExpenseDetail::whereHeader_id($request->expenseId)->get();
            foreach($detail as $d){
                $det = new ExpenseDetail;
                $det->header_id = $head->id;
                $det->category_id = $d->category_id;
                $det->remarks = $d->remarks;
                $det->amount = $d->amount;
                $det->estdate = $d->estdate;
                $det->created_at = date('Y-m-d H:i:s');
                $det->save();
            }
        }
        Expense::whereId($request->expenseId)->update(['is_copied' => 1]);
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
        $this->header = Expense::findorfail($id);
        $this->detail = ExpenseDetail::whereHeader_id($id)->get();
        $this->approval = ExpenseApproval::whereHeader_id($id)->whereNotIn('id',ExpenseApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        $this->inword = $this->numerator($this->header->price);

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('expenses.pdf.expense', $this->data);
        $filename = date('Y-m-d H:i:s');

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    protected function deleteRecords($request){
        abort_403(user()->permission('delete_expenses') == 'all');
        // Did this to call observer
        foreach (Expense::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get() as $delete) {
            $delete->delete();
        }
    }

    protected function changeBulkStatus($request){
        abort_403(user()->permission('edit_employees') != 'all');

        $expenses = Expense::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get();
        $expenses->each(function ($expense) use ($request) {
            $expense->status = $request->status;
            $expense->save();
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
        else if(user()->permission('add_expenses') == 'all' && is_null($request->userId))
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
        $expenseCategory = ExpensesCategoryRole::where('expenses_category_id', $request->categoryId)->get();
        $roleId = [];
        $managers = [];
        $employees = [];

        foreach($expenseCategory as $category) {
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

    private function generateCode($urgency,$employment){
        $last = Expense::whereRaw('year(created_at)="'.date('Y').'"')->whereRaw('month(created_at)="'.date('m').'"')->latest('id');
        if($last->count() > 0){
            $data = $last->first();
            return "PA".$urgency.$employment.date('ymd').str_pad(((int)substr($data->code,10,4) +1), 4, '0', STR_PAD_LEFT);
        }else{
            return "PA".$urgency.$employment.date('ymd')."0001";
        }
    }

    protected function generateApproval($id,$urgency,$employment,$price){
        $apvid = Approval::whereNameAndCompany_idAndIs_urgencyAndIs_employment('expense',user()->company_id,$urgency,$employment)
                ->where('limit_min', '<=', $price)
                ->Where(function ($query) use ($price) {
                    $query->where('limit_max', '0')
                    ->orWhere('limit_max', '>=', $price);
                })->value('id');
        
        $expapv = Expense::whereId($id)->value('approval_id');
        if($apvid <> $expapv){
            ExpenseApproval::Where('header_id',$id)->delete();
            
            if(ExpenseApproval::count()>0){
                $expapv = ExpenseApproval::latest('id')->first();
                $expact = ExpenseAct::latest('id')->first();
                DB::statement('ALTER TABLE expense_approval AUTO_INCREMENT = '.$expapv->id.';');
                DB::statement('ALTER TABLE expense_act AUTO_INCREMENT = '.$expact->id.';');
            }
          
            Expense::whereId($id)->update(['approval_id' => $apvid ]);
            $state = ApprovalState::whereApproval_id($apvid)->get();

            foreach($state as $s){
                if(!filter_var($s->users, FILTER_VALIDATE_INT)){
                    $user = $this->getUserApproval($s->users,user()->id);
                }else{
                    $user = $s->users;
                }
                $apv = new ExpenseApproval;
                $apv->header_id = $id;
                $apv->state_id = $s->state_id;
                $apv->user_id = $user;
                $apv->save();

                $apvact = ApprovalAct::whereState_idAndApproval_id($s->state_id,$apvid)->get();

                foreach($apvact as $aa){
                    $act = new ExpenseAct;
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