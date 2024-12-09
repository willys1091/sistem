<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Settlements;
use App\Models\SettlementDetail;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class SettlementsDetailDataTable extends BaseDataTable{
    use General;
    private $editSettlementPermission;
    private $deleteSettlementPermission;
    private $viewSettlementPermission;
    private $approveSettlementPermission;

    public function __construct($includeSoftDeletedProjects = false){
        parent::__construct();
        $this->editSettlementPermission = user()->permission('edit_settlements');
        $this->deleteSettlementPermission = user()->permission('delete_settlements');
        $this->viewSettlementPermission = user()->permission('view_settlements');
        $this->approveSettlementPermission = user()->permission('approve_settlements');
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">
                        <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            if ($row->status == 'draft') {
                $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
            }
            $action .= '</div></div></div>';
            return $action;
        });
        $datatables->editColumn('category_name', function ($row) {
            return $row->category_name;
        });
        $datatables->addColumn('remarks', function ($row) {
            return $row->remarks??'-';
        });
        $datatables->editColumn('amount', function ($row) {
            return $row->total_amount;
        });
        $datatables->editColumn('estdate',function ($row) {
            if (!is_null($row->estdate)) {
                return $row->estdate->translatedFormat($this->company->date_format);
            }
        });
        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->addIndexColumn();
        $datatables->removeColumn('updated_at');
        $datatables->removeColumn('created_at');

        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check']));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $model = SettlementDetail::select('settlement_detail.id', 'expenses_category_detail.category_name', 'settlement_detail.remarks', 'settlement_detail.amount', 'settlement_detail.estdate',  'settlement_detail.created_at',  'settlements.status')
            ->join('settlements', 'settlements.id', 'settlement_detail.header_id')
            ->join('expenses_category_detail', 'expenses_category_detail.id', 'settlement_detail.category_id');
        return $model->where('settlement_detail.header_id', '=', $request->segment(3));
        //return $model;
    }

    public function html(){
        $dataTable = $this->setBuilder('settlementsdetail-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["settlementsdetail-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-settlement-status").selectpicker();
                }',
            ]);
        return $dataTable;
    }

    protected function getColumns(){
        $data = [
            '#' => ['data' => 'DT_RowIndex','title' =>'#', 'orderable' => false, 'searchable' => false, 'visible' => !showId()],
            __('app.id') => ['data' => 'id', 'name' => 'settlements.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.settlementCategory.categoryName') => ['data' => 'category_name', 'name' => 'category_name', 'title' => __('modules.settlementCategory.categoryName')],
            __('modules.settlements.remarks') => ['data' => 'remarks', 'name' => 'remarks', 'title' => __('modules.settlements.remarks')],
            __('app.price') => ['data' => 'amount', 'name' => 'amount', 'title' => __('app.price')],
            __('modules.settlements.purchaseDate') => ['data' => 'estdate', 'name' => 'estdate', 'title' => __('modules.settlements.purchaseDate')]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, $action);
    }
}