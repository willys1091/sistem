<?php

namespace App\Http\Controllers;

use App\DataTables\ReimbursementCategoryReportDataTable;
use Illuminate\Http\Request;
use App\DataTables\ReimbursementReportDataTable;
use App\Helper\Reply;
use App\Models\Currency;
use App\Models\Reimbursement;
use App\Models\ReimbursementsCategory;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReimbursementReportController extends AccountBaseController{
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.reimbursementReport';
        $this->categoryTitle = 'modules.reimbursementCategory.reimbursementCategoryReport';
    }

    public function index(ReimbursementReportDataTable $dataTable){
           abort_403(user()->permission('view_reimbursement_report') != 'all');
        $this->fromDate = now($this->company->timezone)->startOfMonth();
        $this->toDate = now($this->company->timezone);
        $this->currencies = Currency::all();
        $this->currentCurrencyId = $this->company->currency_id;

        $this->projects = Project::allProjects();
        $this->employees = User::withRole('employee')->get();
        $this->categories = ReimbursementsCategory::get();

        return $dataTable->render('reports.reimbursement.index', $this->data);
    }

    public function reimbursementChartData(Request $request){
        $startDate = ($request->startDate == null) ? null : now($this->company->timezone)->startOfMonth()->toDateString();
        $endDate = ($request->endDate == null) ? null : now($this->company->timezone)->toDateString();

        // Reimbursement report start
        $reimbursements = Reimbursement::where('status', 'approved');

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $reimbursements = $reimbursements->where(DB::raw('DATE(`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $reimbursements = $reimbursements->where(DB::raw('DATE(`purchase_date`)'), '<=', $endDate);
        }

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $reimbursements = $reimbursements->where('category_id', '=', $request->categoryID);
        }

        if ($request->projectID != 'all' && !is_null($request->projectID)) {
            $reimbursements = $reimbursements->where('project_id', '=', $request->projectID);
        }

        if ($request->employeeID != 'all' && !is_null($request->employeeID)) {
            $employeeID = $request->employeeID;
            $reimbursements = $reimbursements->where(function ($query) use ($employeeID) {
                $query->where('user_id', $employeeID);
            });
        }

        $reimbursements = $reimbursements->orderBy('purchase_date', 'ASC')
            ->get([
                DB::raw('DATE_FORMAT(purchase_date,"%d-%M-%y") as date'),
                DB::raw('YEAR(purchase_date) year, MONTH(purchase_date) month'),
                'price',
                'user_id',
                'project_id',
                'currency_id',
                'exchange_rate',
                'default_currency_id',
                'category_id',
            ]);
        $prices = array();

        foreach ($reimbursements as $reimbursement) {
            if (!isset($prices[$reimbursement->date])) {
                $prices[$reimbursement->date] = 0;
            }

            $prices[$reimbursement->date] += $reimbursement->default_currency_price;
        }

        $dates = array_keys($prices);

        $graphData = array();

        foreach ($dates as $date) {
            $graphData[] = [
                'date' => $date,
                'total' => isset($prices[$date]) ? round($prices[$date], 2) : 0,
            ];
        }

        usort($graphData, function ($a, $b) {
            $t1 = strtotime($a['date']);
            $t2 = strtotime($b['date']);
            return $t1 - $t2;
        });

        $graphData = collect($graphData);

        $data['labels'] = $graphData->pluck('date')->toArray();
        $data['values'] = $graphData->pluck('total')->toArray();
        $totalReimbursement = $graphData->sum('total');
        $data['colors'] = [$this->appTheme->header_color];
        $data['name'] = __('modules.dashboard.totalReimbursements');
        $this->chartData = $data;
        // Reimbursement report end

        // Reimbursement category report start

        $startDate = ($request->startDate == null) ? null : now($this->company->timezone)->startOfMonth()->toDateString();
        $endDate = ($request->endDate == null) ? null : now($this->company->timezone)->toDateString();
        $reimbursementCategoryId = ReimbursementsCategory::join('reimbursements', 'reimbursements_category.id', '=', 'reimbursements.category_id')
            ->where('reimbursements.status', 'approved')->where('reimbursements.category_id', '!=', null);

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $reimbursements = $reimbursementCategoryId->where(DB::raw('DATE(reimbursements.`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $reimbursements = $reimbursementCategoryId->where(DB::raw('DATE(reimbursements.`purchase_date`)'), '<=', $endDate);
        }

        if ($request->employeeID != 'all' && !is_null($request->employeeID)) {
            $reimbursementCategoryId = $reimbursementCategoryId->where('reimbursements.user_id', $request->employeeID);
        }

        if ($request->projectID != 'all' && !is_null($request->projectID)) {
            $reimbursementCategoryId = $reimbursementCategoryId->where('reimbursements.project_id', $request->projectID);
        }

        $reimbursementCategoryId = $reimbursementCategoryId->distinct('reimbursements.category_id')->selectRaw('reimbursements.category_id as id')->pluck('id')->toArray();
        $categories = ReimbursementsCategory::whereIn('id', $reimbursementCategoryId)->get();

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $categories = $categories->where('id', $request->categoryID);
        }

        $barData['labels'] = $categories->pluck('category_name');
        $barData['name'] = __('modules.reports.totalCategories');
        $barData['colors'] = [$this->appTheme->header_color];
        $barData['values'] = [];

        foreach ($categories as $category) {
            /** @phpstan-ignore-next-line */
            $category_id = isset($category->id) ? $category->id : $category->category_id;

            if ($startDate && $endDate != null) {
                $barData['values'][] = Reimbursement::where('category_id', $category_id)->whereBetween(DB::raw('DATE(`purchase_date`)'), [$startDate, $endDate])->count();
            }else{
                $barData['values'][] = Reimbursement::where('category_id', $category_id)->count();
            }
        }

        $this->barChartData = $barData;
        // Reimbursement category report end
        $html = view('reports.reimbursement.chart', $this->data)->render(); /* Reimbursement report view */
        $html2 = view('reports.reimbursement.bar_chart', $this->data)->render(); /* Reimbursement Category report view */
        return Reply::dataOnly(['status' => 'success', 'html' => $html,'html2' => $html2, 'title' => $this->pageTitle, 'totalReimbursements' => currency_format($totalReimbursement, company()->currency_id)]);
    }

    public function reimbursementCategoryReport(){
        abort_403(user()->permission('view_reimbursement_report') != 'all');
        $dataTable = new ReimbursementCategoryReportDataTable();
        $this->fromDate = now($this->company->timezone)->startOfMonth();
        $this->toDate = now($this->company->timezone);
        $this->categories = ReimbursementsCategory::get();
        return $dataTable->render('reports.reimbursement.reimbursement-category-report', $this->data);
    }
}