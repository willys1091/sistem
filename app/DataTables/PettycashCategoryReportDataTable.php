<?php

namespace App\DataTables;

use App\Models\PettycashesCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;

class PettycashCategoryReportDataTable extends BaseDataTable{
    public function dataTable($query){
        return datatables()
            ->eloquent($query)
            ->addColumn('converted_price', function ($row) {
                return currency_format($row->pettycashes->sum('default_currency_price'), company()->currency_id);
            })
            ->smart(false)
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->addIndexColumn();
    }

    public function query(PettycashesCategory $model){
        $request = $this->request();

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $model = $model->where('id', '=', $request->categoryID);
        }

        $model = $model->with(['pettycashes' => function ($query) use ($request) {
            if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
                $startDate = companyToDateString($request->startDate);
                $query->where(DB::raw('DATE(`purchase_date`)'), '>=', $startDate);
            }

            if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
                $endDate = companyToDateString($request->endDate);
                $query->where(DB::raw('DATE(`purchase_date`)'), '<=', $endDate);
            }

            $query->where('status', 'approved');
        }]);

        return $model;
    }

    public function html(){
        $dataTable = $this->setBuilder('pettycash-category-report-table')
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["pettycash-category-report-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("#pettycash-category-report-table .select-picker").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    protected function getColumns(){
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => true, 'title' => '#'],
            __('app.category') => ['data' => 'category_name', 'name' => 'category_name', 'title' => __('app.category')],
            __('app.total').' '.__('app.price') => ['data' => 'converted_price', 'name' => 'converted_price', 'orderable' => false, 'title' => __('app.total').' '.__('app.price')],
        ];
    }
}