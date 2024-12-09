<?php

namespace App\DataTables;
use Carbon\Carbon;
use App\Traits\General;
use App\Models\Expense;
use App\Models\ExpenseAct;
use App\Models\ExpenseApproval;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class FaReportsExpensesDataTable extends BaseDataTable{
    use General;

    public function __construct($includeSoftDeletedProjects = false){
        parent::__construct();
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
           
            $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';
            $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-expense-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
            $action .= '<a href="' . route('expenses.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            
            // if($row->status == 'approved' || $row->status == 'paid'){
            //     $action .= '<a class="dropdown-item" target="_blank" href="' . route('expenses.download', [$row->id, 'view' => true]) . '"><i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '</a>';
            // }

            $action .= '</div></div></div>';
            return $action;
        });

        $datatables->editColumn('code', function ($row) {
            return $row->code;
        });

        $datatables->editColumn('price', function ($row) {
            return $row->total_amount;
        });

        $datatables->editColumn('item_name', function ($row) {
            if (is_null($row->expenses_recurring_id)) {
                return '<a href="' . route('expenses.show', [$row->id]) . '" class="text-darkest-grey">' . $row->item_name . '</a>';
            }
            return '<a href="' . route('expenses.show', $row->id) . '" class="text-darkest-grey">' . $row->item_name . '</a>
                <p class="mb-0"><span class="badge badge-primary"> ' . __('app.recurring') . ' </span></p>';
        });

        $datatables->addColumn('export_item_name', function ($row) {
            return $row->item_name;
        });

        $datatables->addColumn('urgency', function ($row) {
            return ucfirst($row->urgency);
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
            if ($row->status == 'on progress') {
                $class = 'text-warning';
                $status = __('app.onProgress');
            }else if ($row->status == 'approved') {
                $class = 'text-dark-green';
                $status = __('app.approved');
            } else {
                $class = 'text-red';
                $status = __('app.rejected');
            }
            $status = '<i class="fa fa-circle mr-1 ' . $class . ' f-10"></i> ' . $status . " " . explode(" ", $row->state_name)[0];
            return $status;
        });

        $datatables->addColumn('status_export', function ($row) {
            return $row->status;
        });

        $datatables->editColumn('purchase_date',function ($row) {
            if (!is_null($row->purchase_date)) {
                return $row->purchase_date->translatedFormat($this->company->date_format);
            }
         });

        $datatables->editColumn('purchase_from',function ($row) {
            return !is_null($row->purchase_from) ? $row->purchase_from : '--';
        });

        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->addIndexColumn();
        $datatables->removeColumn('currency_id');
        $datatables->removeColumn('name');
        $datatables->removeColumn('currency_symbol');
        $datatables->removeColumn('updated_at');
        $datatables->removeColumn('created_at');
        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatables, Expense::CUSTOM_FIELD_MODEL);
        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check'], $customFieldColumns));
        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $approval = ExpenseApproval::whereUser_id(user()->id);
        $model = Expense::with('currency','user','user.employeeDetail','user.employeeDetail.designation','user.session')->select(
                'expenses.id','expenses.code','expenses.project_id','expenses.item_name','expenses.user_id','expense_approval.user_id as approval_user_id','expenses.urgency','expenses.price','users.salutation','users.name','expenses.purchase_date','expenses.currency_id','currencies.currency_symbol','expenses.status','expenses.state_id','expenses.purchase_from','expenses.expenses_recurring_id','designations.name as designation_name','expenses.added_by','projects.deleted_at as project_deleted_at','expenses.is_copied','approval_state.name as state_name','expenses.is_detail'
                )->join('users','users.id','expenses.user_id'
                )->leftJoinSub($approval,'approval',function ($join) {
                        $join->on('approval.header_id', '=', 'expenses.id');
                })->leftJoin('expense_approval',function ($join) {
                    $join->on('expense_approval.header_id', '=', 'expenses.id');
                    $join->on('expense_approval.state_id', '=', 'expenses.state_id');
                })->leftJoin('approval_state',function ($join) {
                    $join->on('approval_state.approval_id', '=', 'expenses.approval_id');
                    $join->on('approval_state.state_id', '=', 'expenses.state_id');
                })->leftJoin('employee_details','employee_details.user_id','=','users.id'
                )->leftJoin('designations','employee_details.designation_id','=','designations.id'
                )->leftJoin('projects','projects.id','expenses.project_id'
                )->join('currencies','currencies.id','expenses.currency_id');
                if (!$this->includeSoftDeletedProjects) {
                    $model->whereNull('projects.deleted_at');
                }
                // if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
                //     $startDate = companyToDateString($request->startDate);
                //     $model = $model->where(DB::raw('DATE(expenses.`purchase_date`)'), '>=', $startDate);
                // }
                // if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
                //     $endDate = companyToDateString($request->endDate);
                //     $model = $model->where(DB::raw('DATE(expenses.`purchase_date`)'), '<=', $endDate);
                // }
                // if ($request->status != 'all' && !is_null($request->status)) {
                //     $model = $model->where('expenses.status', '=', $request->status);
                // }
                // if ($request->projectId != 'all' && !is_null($request->projectId)) {
                //     $model = $model->where('expenses.project_id', '=', $request->projectId);
                // }
                // if ($request->categoryId != 'all' && !is_null($request->categoryId)) {
                //     $model = $model->where('expenses.category_id', '=', $request->categoryId);
                // }
                // if ($request->recurringID != '') {
                //     $model = $model->where('expenses.expenses_recurring_id', '=', $request->recurringID);
                // }
                // if ($request->searchText != '') {
                //     $model->where(function ($query) {
                //         $query->where('expenses.code', 'like', '%' . request('searchText') . '%')
                //             ->orWhere('expenses.item_name', 'like', '%' . request('searchText') . '%')
                //             ->orWhere('users.name', 'like', '%' . request('searchText') . '%')
                //             ->orWhere('expenses.price', 'like', '%' . request('searchText') . '%');
                //     });
                // }
            $model->whereRaw("`expenses`.`status` in ('on progress','approved','rejected')");
        return $model->groupBy('expenses.id');
    }

    public function html(){
        $dataTable = $this->setBuilder('expenses-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["expenses-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-expense-status").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }
        return $dataTable;
    }

    protected function getColumns(){
        $data = [
            // 'check' => [
            //     'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
            //     'exportable' => false,
            //     'orderable' => false,
            //     'searchable' => false
            // ],
            '#' => ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'visible' => !showId()],
            __('app.id') => ['data' => 'id', 'name' => 'expenses.id', 'title' => __('app.id'), 'visible' => showId()],
            __('modules.expenses.code') => ['data' => 'code', 'name' => 'code', 'exportable' => false, 'title' => __('modules.expenses.code')],
            __('modules.expenses.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'exportable' => false, 'title' => __('modules.expenses.itemName')],
            __('app.menu.itemName') => ['data' => 'export_item_name', 'name' => 'export_item_name', 'visible' => false, 'title' => __('modules.expenses.itemName')],
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],
            __('modules.expenses.urgency') => ['data' => 'urgency', 'name' => 'urgency', 'title' => __('modules.expenses.urgency')],
            __('app.menu.employees') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.menu.employees')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.employee')],
            __('modules.expenses.purchaseDate') => ['data' => 'purchase_date', 'name' => 'purchase_date', 'title' => __('modules.expenses.purchaseDate')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
            __('app.expense') . ' ' . __('app.status') => ['data' => 'status_export', 'name' => 'status', 'visible' => false, 'title' => __('app.expense')]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Expense()), $action);
    }
}
