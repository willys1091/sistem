<?php

namespace App\Http\Controllers;

use App\DataTables\FaReportsExpensesDataTable;
use App\DataTables\FaReportsReimbursementsDataTable;
use App\DataTables\FaReportsSettlementsDataTable;
use App\DataTables\FaReportsPettyCashesDataTable;
use App\Traits\General;
use Illuminate\Http\Request;
use App\Http\Controllers\AccountBaseController;
use App\DataTables\FinanceExecutorsExpensesDataTable;


class FaReportController extends AccountBaseController
{
    use General;

    public function __construct()
    {

        parent::__construct();

        $this->pageTitle = 'app.menu.faReport';

        $this->middleware(function ($request, $next) {

            abort_403(!in_array('reports', $this->user->modules));
            // dd($this->user->modules);

            return $next($request);
        });
    }

    public function index()
    {
        $viewPermission = user()->permission('view_fa_progress_report');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $tab = request("tab");

        switch ($tab) {
            case "settlements":
                return $this->settlements();
                break;
            case "reimbursements":
                return $this->reimbursements();
                break;
            case "pettycashes":
                return $this->pettyCashes();
                break;
            default:
                return $this->expenses();
                break;
        }

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: "expenses";

        return view('reports.fa.index', $this->data);
    }

    private function expenses()
    {

        $dataTable = new FaReportsExpensesDataTable();

        $tab = request('tab');

        $this->activeTab = $tab ?: 'expenses';

        $this->view = 'reports.fa.ajax.expenses';

        return $dataTable->render('reports.fa.index', $this->data);
    }

    private function settlements()
    {

        $dataTable = new FaReportsSettlementsDataTable();

        $tab = request('tab');

        $this->activeTab = $tab ?: 'expenses';

        $this->view = 'reports.fa.ajax.settlements';

        return $dataTable->render('reports.fa.index', $this->data);
    }

    private function reimbursements()
    {

        $dataTable = new FaReportsReimbursementsDataTable();

        $tab = request('tab');

        $this->activeTab = $tab ?: 'expenses';

        $this->view = 'reports.fa.ajax.reimbursements';

        return $dataTable->render('reports.fa.index', $this->data);
    }

    private function pettyCashes()
    {
        $dataTable = new FaReportsPettyCashesDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: "expenses";
        $this->view = "reports.fa.ajax.pettycashes";

        return $dataTable->render('reports.fa.index', $this->data);
    }
}
