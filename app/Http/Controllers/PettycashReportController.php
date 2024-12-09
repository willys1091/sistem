<?php

namespace App\Http\Controllers;

use App\DataTables\PettycashCategoryReportDataTable;
use Illuminate\Http\Request;
use App\DataTables\PettycashReportDataTable;
use App\Helper\Reply;
use App\Models\Currency;
use App\Models\Pettycash;
use App\Models\PettycashesCategory;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PettycashReportController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.pettycashReport';
        $this->categoryTitle = 'modules.pettycashCategory.pettycashCategoryReport';
    }

    public function index(PettycashReportDataTable $dataTable)
    {
           abort_403(user()->permission('view_pettycash_report') != 'all');
        $this->fromDate = now($this->company->timezone)->startOfMonth();
        $this->toDate = now($this->company->timezone);
        $this->currencies = Currency::all();
        $this->currentCurrencyId = $this->company->currency_id;

        $this->projects = Project::allProjects();
        $this->employees = User::withRole('employee')->get();
        $this->categories = PettycashesCategory::get();

        return $dataTable->render('reports.pettycash.index', $this->data);
    }

    public function pettycashChartData(Request $request)
    {
        $startDate = ($request->startDate == null) ? null : now($this->company->timezone)->startOfMonth()->toDateString();
        $endDate = ($request->endDate == null) ? null : now($this->company->timezone)->toDateString();

        // Pettycash report start
        $pettycashes = Pettycash::where('status', 'approved');

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $pettycashes = $pettycashes->where(DB::raw('DATE(`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $pettycashes = $pettycashes->where(DB::raw('DATE(`purchase_date`)'), '<=', $endDate);
        }

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $pettycashes = $pettycashes->where('category_id', '=', $request->categoryID);
        }

        if ($request->projectID != 'all' && !is_null($request->projectID)) {
            $pettycashes = $pettycashes->where('project_id', '=', $request->projectID);
        }

        if ($request->employeeID != 'all' && !is_null($request->employeeID)) {
            $employeeID = $request->employeeID;
            $pettycashes = $pettycashes->where(function ($query) use ($employeeID) {
                $query->where('user_id', $employeeID);
            });
        }

        $pettycashes = $pettycashes->orderBy('purchase_date', 'ASC')
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

        foreach ($pettycashes as $pettycash) {
            if (!isset($prices[$pettycash->date])) {
                $prices[$pettycash->date] = 0;
            }

            $prices[$pettycash->date] += $pettycash->default_currency_price;
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
        $totalPettycash = $graphData->sum('total');
        $data['colors'] = [$this->appTheme->header_color];
        $data['name'] = __('modules.dashboard.totalPettycashes');
        $this->chartData = $data;
        // Pettycash report end

        // Pettycash category report start

        $startDate = ($request->startDate == null) ? null : now($this->company->timezone)->startOfMonth()->toDateString();
        $endDate = ($request->endDate == null) ? null : now($this->company->timezone)->toDateString();
        $pettycashCategoryId = PettycashesCategory::join('pettycashes', 'pettycashes_category.id', '=', 'pettycashes.category_id')
            ->where('pettycashes.status', 'approved')
            ->where('pettycashes.category_id', '!=', null);

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $pettycashes = $pettycashCategoryId->where(DB::raw('DATE(pettycashes.`purchase_date`)'), '>=', $startDate);
        }


        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $pettycashes = $pettycashCategoryId->where(DB::raw('DATE(pettycashes.`purchase_date`)'), '<=', $endDate);
        }


        if ($request->employeeID != 'all' && !is_null($request->employeeID)) {
            $pettycashCategoryId = $pettycashCategoryId->where('pettycashes.user_id', $request->employeeID);
        }

        if ($request->projectID != 'all' && !is_null($request->projectID)) {
            $pettycashCategoryId = $pettycashCategoryId->where('pettycashes.project_id', $request->projectID);
        }


        $pettycashCategoryId = $pettycashCategoryId->distinct('pettycashes.category_id')->selectRaw('pettycashes.category_id as id')->pluck('id')->toArray();

        $categories = PettycashesCategory::whereIn('id', $pettycashCategoryId)->get();

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
                $barData['values'][] = Pettycash::where('category_id', $category_id)->whereBetween(DB::raw('DATE(`purchase_date`)'), [$startDate, $endDate])->count();
            }
            else{
                $barData['values'][] = Pettycash::where('category_id', $category_id)->count();
            }
        }

        $this->barChartData = $barData;
        // Pettycash category report end

        $html = view('reports.pettycash.chart', $this->data)->render(); /* Pettycash report view */
        $html2 = view('reports.pettycash.bar_chart', $this->data)->render(); /* Pettycash Category report view */

        return Reply::dataOnly(['status' => 'success', 'html' => $html,'html2' => $html2, 'title' => $this->pageTitle, 'totalPettycashes' => currency_format($totalPettycash, company()->currency_id)]);
    }

    public function pettycashCategoryReport()
    {
        abort_403(user()->permission('view_pettycash_report') != 'all');
        $dataTable = new PettycashCategoryReportDataTable();

        $this->fromDate = now($this->company->timezone)->startOfMonth();
        $this->toDate = now($this->company->timezone);
        $this->categories = PettycashesCategory::get();

        return $dataTable->render('reports.pettycash.pettycash-category-report', $this->data);
    }

}
