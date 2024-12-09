<?php

namespace App\Http\Controllers;

use App\DataTables\AccChecksExpensesDataTable;
use App\DataTables\AccChecksSettlementsDataTable;
use App\DataTables\AccChecksReimbursementsDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\AccChecks\StoreAccCheck;
use App\Models\Expense;
use App\Models\Reimbursement;
use App\Models\Settlement;
use App\Traits\General;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccCheckController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.accChecks';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('accChecks', $this->user->modules));
            return $next($request);
        });
    }

    public function index(){
        $viewPermission = user()->permission('view_accChecks');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $tab = request('tab');

        switch ($tab) {
            case 'settlements':
                return $this->settlements();
                break;
            // case 'reimbursements':
            //     return $this->reimbursements();
            //     break;
            default:
                return $this->expenses();
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'expenses';

        return view('acc-checks.index', $this->data);
    }

    private function expenses(){
        $dataTable = new AccChecksExpensesDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'acc-checks.ajax.expenses';
        return $dataTable->render('acc-checks.index', $this->data);
    }

    private function settlements(){
        $dataTable = new AccChecksSettlementsDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'acc-checks.ajax.settlements';
        return $dataTable->render('acc-checks.index', $this->data);
    }

    private function reimbursements(){
        $dataTable = new AccChecksReimbursementsDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'expenses';
        $this->view = 'acc-checks.ajax.reimbursements';
        return $dataTable->render('acc-checks.index', $this->data);
    }

    public function expensesCheck(){
        $this->expenseId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('acc-checks.ajax.expensesCheck', $this->data);
    }

    public function actionExpensesCheck(StoreAccCheck $request){
        $expense = Expense::findOrFail($request->expense_id);
        $expense->acc_no = $request->accNo;
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Expense::FILE_PATH_ACC);
            $expense->acc_file = $filename;
        }
        $expense->status = 'done';
        $expense->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('accChecks.index')]);
    }

    public function reimbursementsCheck(){
        $this->reimbursementId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('acc-checks.ajax.reimbursementsCheck', $this->data);
    }

    public function actionReimbursementsCheck(StoreAccCheck $request){
        $reimbursement = Reimbursement::findOrFail($request->reimbursement_id);
        $reimbursement->acc_no = $request->accNo;
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Reimbursement::FILE_PATH_ACC);
            $reimbursement->acc_file = $filename;
        }
        $reimbursement->status = 'done';
        $reimbursement->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('accChecks.index',['','tab=reimbursements'])]);
    }

    public function settlementsCheck(){
        $this->settlementId = request('id');
        $this->redirectUrl = request('redirectUrl');
        return view('acc-checks.ajax.settlementsCheck', $this->data);
    }

    public function actionSettlementsCheck(StoreAccCheck $request){
        $settlement = Settlement::findOrFail($request->settlement_id);
        $settlement->acc_no = $request->accNo;
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Settlement::FILE_PATH_ACC);
            $settlement->acc_file = $filename;
        }
        $settlement->status = 'done';
        $settlement->save();
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('accChecks.index',['','tab=settlements'])]);
    }
}