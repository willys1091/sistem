<?php

namespace App\Http\Controllers;

use App\DataTables\FinanceExecutorsExpensesDataTable;
use App\DataTables\FinanceExecutorsSettlementsDataTable;
use App\DataTables\FinanceExecutorsReimbursementsDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\FinanceExecutors\StoreFinanceExecutor;
use App\Models\Expense;
use App\Models\Reimbursement;
use App\Models\Settlement;
use App\Traits\General;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceExecutorController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.financeExecutors';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('financeExecutors', $this->user->modules));
            return $next($request);
        });
    }

    public function index(){
        $viewPermission = user()->permission('view_financeExecutors');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $tab = request('tab');

        switch ($tab) {
            case 'settlements':
                return $this->settlements();
                break;
            case 'reimbursements':
                return $this->reimbursements();
                break;
            default:
                return $this->expenses();
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'expenses';

        return view('finance-executors.index', $this->data);
    }

    private function expenses(){
        $dataTable = new FinanceExecutorsExpensesDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'finance-executors.ajax.expenses';
        return $dataTable->render('finance-executors.index', $this->data);
    }

    private function settlements(){
        $dataTable = new FinanceExecutorsSettlementsDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'finance-executors.ajax.settlements';
        return $dataTable->render('finance-executors.index', $this->data);
    }

    private function reimbursements(){
        $dataTable = new FinanceExecutorsReimbursementsDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'finance-executors.ajax.reimbursements';
        return $dataTable->render('finance-executors.index', $this->data);
    }

    public function expensesPaid(){
        $this->expenseId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('finance-executors.ajax.expensesPaid', $this->data);
    }

    public function actionExpensesPaid(StoreFinanceExecutor $request){
        $expense = Expense::findOrFail($request->expense_id);
        $expense->is_fin_exe = 1;
        $expense->fin_date = companyToYmd($request->transferDate);
        $expense->status = 'paid';
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, 'finance-executor');
            $expense->fin_file = $filename;
        }   
        $expense->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('financeExecutors.index')]);
    }

    public function reimbursementsPaid(){
        $this->reimbursementId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('finance-executors.ajax.reimbursementsPaid', $this->data);
    }

    public function actionReimbursementsPaid(StoreFinanceExecutor $request){
        $reimbursement = Reimbursement::findOrFail($request->reimbursement_id);
        $reimbursement->is_fin_exe = 1;
        $reimbursement->fin_date = companyToYmd($request->transferDate);
        $reimbursement->status = 'paid';
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, 'finance-executor');
            $reimbursement->fin_file = $filename;
        }   
        $reimbursement->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('financeExecutors.index',['','tab=reimbursements'])]);
    }

    public function settlementsPaid(){
        $this->settlementId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('finance-executors.ajax.settlementsPaid', $this->data);
    }

    public function actionSettlementsPaid(StoreFinanceExecutor $request){
        $settlement = Settlement::findOrFail($request->settlement_id);
        $settlement->is_fin_exe = 1;
        $settlement->fin_date = companyToYmd($request->transferDate);
        $settlement->status = 'paid';
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, 'finance-executor');
            $settlement->fin_file = $filename;
        }   
        $settlement->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('financeExecutors.index',['','tab=settlements'])]);
    }
}