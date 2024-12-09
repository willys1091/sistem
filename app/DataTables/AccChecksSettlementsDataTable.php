<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Settlement;
use App\Models\SettlementAct;
use App\Models\SettlementApproval;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class AccChecksSettlementsDataTable extends BaseDataTable{
    use General;

    public function __construct(){
        parent::__construct();
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', fn($row) => $this->checkBox($row));
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
            $action .= '<a class="task_view_more d-flex align-items-center justify-content-center text-success check" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa fa-check mr-2"></i></a>';
            $action .= '</div>';
            return $action;
        });
        $datatables->editColumn('code', function ($row) {
            return $row->code;
        });
        $datatables->editColumn('expense_code', function ($row) {
            return $row->expense_code;
        });
        $datatables->editColumn('item_name', function ($row) {
            return '<a href="' . route('settlements.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>';
        });
        $datatables->addColumn('export_item_name', function ($row) {
            return $row->item_name;
        });
        $datatables->editColumn('price', function ($row) {
            return $row->price;
        });
        $datatables->addColumn('employee_name', function ($row) {
            return $row->user->name;
        });
        $datatables->editColumn('user_id', function ($row) {
            return view('components.employee', [
                'user' => $row->user
            ]);
        });
        
        $datatables->editColumn('status', function ($row) { 
            if ($row->status == 'paid') {
                $class = 'text-red';
                $status = __('app.notCheck');
            }
            else {
                $class = 'text-red';
                $status = __('app.rejected');
            }
            $status = '<i class="fa fa-circle mr-1 ' . $class . ' f-10"></i> ' . $status;

            return $status;
        });
       
        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->addIndexColumn();
        $datatables->removeColumn('currency_id');
        $datatables->removeColumn('name');
        $datatables->removeColumn('currency_symbol');
        $datatables->removeColumn('updated_at');
        $datatables->removeColumn('created_at');

        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check']));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $approval = SettlementApproval::whereUser_id(user()->id);

        $model = Settlement::select('settlements.id' ,'settlements.code', 'expenses.code as expense_code', 'expenses.item_name as item_name' , 'expenses.user_id', 'settlement_approval.user_id as approval_user_id', 'settlements.price', 'expenses.purchase_date', 'expenses.currency_id', 'currencies.currency_symbol', 'settlements.status','settlements.state_id', 'settlements.is_copied','approval_state.name as state_name')
            ->join('expenses','expenses.id','settlements.expense_id')
            ->join('users', 'users.id', 'expenses.user_id')
            ->leftJoinSub($approval,'approval',function($join){
                $join->on('approval.header_id', '=', 'settlements.id');
            })
            ->leftJoin('settlement_approval', function($join) {
                $join->on('settlement_approval.header_id', '=', 'settlements.id');
                $join->on('settlement_approval.state_id', '=', 'settlements.state_id');
            })
            ->leftJoin('approval_state', function($join) {
                $join->on('approval_state.approval_id', '=', 'settlements.approval_id');
                $join->on('approval_state.state_id', '=', 'settlements.state_id');
            })
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->join('currencies', 'currencies.id', 'expenses.currency_id');
      
            $model->whereRaw("`settlements`.`status`= 'paid'");
            $model->where('expenses.company_id', user()->company_id);
        return $model->groupBy('settlements.id');
    }

    public function html(){
        $dataTable = $this->setBuilder('settlements-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["settlements-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-settlement-status").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    protected function getColumns(){
        $data = [
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false
            ],
            '#' => ['data' => 'DT_RowIndex','title' =>'#', 'orderable' => false, 'searchable' => false, 'visible' => !showId()],
            __('app.id') => ['data' => 'id', 'name' => 'expenses.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.settlements.code') => ['data' => 'code', 'name' => 'code', 'exportable' => false, 'title' => __('modules.settlements.code')],
            __('modules.settlements.expenseCode') => ['data' => 'expense_code', 'name' => 'expense_code', 'exportable' => false, 'title' => __('modules.settlements.expenseCode')],
            __('modules.expenses.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'exportable' => false, 'title' => __('modules.expenses.itemName')],
            __('app.menu.itemName') => ['data' => 'export_item_name', 'name' => 'export_item_name', 'visible' => false, 'title' => __('modules.expenses.itemName')],
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.employee')],
            __('app.menu.employees') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.menu.employees')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
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