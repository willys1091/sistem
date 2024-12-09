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

class SettlementsDataTable extends BaseDataTable{
    use General;
    private $editSettlementPermission;
    private $deleteSettlementPermission;
    private $viewSettlementPermission;
    private $approveSettlementPermission;

    public function __construct(){
        parent::__construct();
        $this->editSettlementPermission = user()->permission('edit_settlements');
        $this->deleteSettlementPermission = user()->permission('delete_settlements');
        $this->viewSettlementPermission = user()->permission('view_settlements');
        $this->approveSettlementPermission = user()->permission('approve_settlements');
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', fn($row) => $this->checkBox($row));
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
            if($row->approval_user_id == user()->id && $row->price > 0){
                $btn = $this->getApprovalAct('settlement',$row->id,$row->state_id);
                    foreach($btn as $b){
                    if($b->name == 'copy document'){
                        if($row->is_copy=='0'){
                            $action .= '<a class="taskView d-flex align-items-center justify-content-center copy text-'.$b->color.'" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                        }
                    }else{
                        $action .= '<a class="taskView d-flex align-items-center justify-content-center open-response text-'.$b->color.'" href="javascript:;" data-settlement-id="' . $row->id . '" data-act-id="' . $b->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                    }
                }
            }
            $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
            if (is_null($row->settlements_recurring_id) && $row->status == 'draft' ) {
                $action .= '<a class="dropdown-item" href="'. route('settlements.show', $row->id).'?tab=detail' .'"><i class="fa fa-plus mr-2"></i>Add Detail</a>';
            }
            $action .= '<a href="' . route('settlements.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            if (is_null($row->settlements_recurring_id) && $row->status == 'draft') {
                if ($this->editSettlementPermission == 'all' || ($this->editSettlementPermission == 'added' && user()->id == $row->user_id)) {
                    // if (is_null($row->project_id)) {
                    //     $action .= '<a class="dropdown-item open-edit" data-settlement-id="' . $row->id . '" href="' . route('settlements.edit', [$row->id]) . '">
                    //             <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    // } else if (!is_null($row->project_id) && is_null($row->project_deleted_at)) {
                    //     $action .= '<a class="dropdown-item open-edit" data-settlement-id="' . $row->id . '" href="' . route('settlements.edit', [$row->id]) . '">
                    //         <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    // }
                    $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-settlement-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                }

                if ($this->deleteSettlementPermission == 'all' || ($this->deleteSettlementPermission == 'added' && user()->id == $row->user_id)) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-settlement-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                }
            }
            if($row->status == 'approved' || $row->status == 'paid'){
                $action .= '<a class="dropdown-item" target="_blank" href="' . route('settlements.download', [$row->id, 'view' => true]) . '"><i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '</a>';
            }

            $action .= '</div></div></div>';
            return $action;
        });
        $datatables->editColumn('code', function ($row) {
            return $row->code;
        });
        $datatables->editColumn('expense_code', function ($row) {
            return $row->expense_code;
        });
        $datatables->editColumn('item_name', function ($row) {            
            if (is_null($row->settlements_recurring_id)) {                
            return '<a href="' . route('settlements.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>';            }            return '<a href="' . route('settlements.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>                    <p class="mb-0">                        <span class="badge badge-primary">' . __('app.recurring') . '</span>                    </p>';        });
        $datatables->addColumn('export_item_name', function ($row) {
            return $row->item_name;
        });
        $datatables->editColumn('price', function ($row) {
            return currency_format($row->price, $row->currency_id);
        });
        $datatables->addColumn('employee_name', function ($row) {
            return $row->user->name;
        });
        $datatables->editColumn('user_id', function ($row) {
            return view('components.employee', [
                'user' => $row->user
            ]);
        });        $datatables->addColumn('urgency', function ($row) {            return ucwords($row->urgency);        });
        $datatables->editColumn('status', function ($row) { 
            if ($row->status == 'draft') {
                $class = 'text-gray';
                $status = __('app.draft');
            }
            else if ($row->status == 'active') {
                $class = 'text-light-blue';
                $status = __('app.active');
            }
            else if ($row->status == 'on progress '. explode(" ",$row->state_name)[0]) {
                $class = 'text-yellow';
                $status = __('app.onProgress').' '. explode(" ",$row->state_name)[0];
            }
            else if ($row->status == 'approved') {
                $class = 'text-light-green';
                $status = __('app.approved');
            }
            else if ($row->status == 'paid') {
                $class = 'text-blue';
                $status = __('app.paid');
            }
            else if ($row->status == 'done') {
                $class = 'text-dark-green';
                $status = __('app.done');
            }
            else {
                $class = 'text-red';
                $status = __('app.rejected');
            }
            $status = '<i class="fa fa-circle mr-1 ' . $class . ' f-10"></i> ' . $status;

            return $status;
        });
        // $datatables->addColumn('status_export', function ($row) {
        //     return $row->status;
        // });

        // $datatables->editColumn(
        //     'purchase_date',
        //     function ($row) {
        //         if (!is_null($row->purchase_date)) {
        //             return $row->purchase_date->translatedFormat($this->company->date_format);
        //         }
        //     }
        // );
        // $datatables->editColumn(
        //     'purchase_from',
        //     function ($row) {
        //         return !is_null($row->purchase_from) ? $row->purchase_from : '--';
        //     }
        // );
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

        $model = Settlement::select('settlements.id' ,'settlements.code', 'expenses.code as expense_code', 'expenses.item_name as item_name' , 'expenses.user_id', 'settlement_approval.user_id as approval_user_id','expenses.urgency as urgency', 'settlements.price', 'expenses.purchase_date', 'expenses.currency_id', 'currencies.currency_symbol', DB::raw("
        CASE 
            WHEN settlements.status <> 'on progress' THEN settlements.status
            ELSE concat('on progress ', SUBSTRING_INDEX( `approval_state`.`name`, ' ', 1 ))
        END AS status
    "),'settlements.state_id', 'settlements.is_copied','approval_state.name as state_name')
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
        $model->where('expenses.company_id', user()->company_id);
        if ($request->stateId != 'all' && !is_null($request->stateId)) {
            $model = $model->where('settlement_approval.user_id', '=', $request->stateId);
            $model = $model->whereNotIn('settlements.state_id', ['1','2']);
        }

        if ($request->status != 'all' && !is_null($request->status)) {
            $model = $model->where('settlements.status', '=', $request->status);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('settlements.code', 'like', '%' . request('searchText') . '%')
                    ->orWhere('expenses.item_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('users.name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('settlements.price', 'like', '%' . request('searchText') . '%');
            });
        }
        
        if ($this->viewSettlementPermission == 'all') {
            $model->whereIn('settlements.status',['draft','active','on progress','approved','rejected','paid','done']);
        }else{
            if ($this->viewSettlementPermission == 'added') {
                $model->where('settlements.user_id', user()->id);
            }
    
            if ($this->viewSettlementPermission == 'owned') {
                $model->where('settlements.user_id', user()->id);
            }
    
            if ($this->viewSettlementPermission == 'both') {
                $model->where(function ($query) {
                    $query->orWhere('settlements.user_id', user()->id)
                        ->orWhere('approval.user_id', user()->id);
                });
            }
            $model->whereRaw("CASE WHEN `approval`.`user_id` = `settlements`.`user_id` THEN  `settlements`.`status` is not null WHEN `approval`.`user_id` is null THEN `settlements`.`status` = 'draft' ELSE `settlements`.`status` <> 'draft' END");
        }
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
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],						__('modules.settlements.urgency') => ['data' => 'urgency', 'name' => 'urgency', 'title' => __('modules.settlements.urgency')],
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