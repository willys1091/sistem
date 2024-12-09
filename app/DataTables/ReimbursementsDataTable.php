<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Reimbursement;
use App\Models\ReimbursementAct;
use App\Models\ReimbursementApproval;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class ReimbursementsDataTable extends BaseDataTable{
    use General;
    private $editReimbursementPermission;
    private $deleteReimbursementPermission;
    private $viewReimbursementPermission;
    private $approveReimbursementPermission;
    private $includeSoftDeletedProjects;

    public function __construct($includeSoftDeletedProjects = false){
        parent::__construct();
        $this->editReimbursementPermission = user()->permission('edit_reimbursements');
        $this->deleteReimbursementPermission = user()->permission('delete_reimbursements');
        $this->viewReimbursementPermission = user()->permission('view_reimbursements');
        $this->approveReimbursementPermission = user()->permission('approve_reimbursements');
        $this->includeSoftDeletedProjects = $includeSoftDeletedProjects;
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', fn($row) => $this->checkBox($row));
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
            if($row->approval_user_id == user()->id && $row->price > 0){
                $btn = $this->getApprovalAct('reimbursement',$row->id,$row->state_id);
                foreach($btn as $b){
                    if($b->name == 'copy document' || $b->name == 'Copy Document'){
                        if($row->is_copied == '0'){
                            $action .= '<a class="taskView d-flex align-items-center justify-content-center copy text-'.$b->color.'" href="javascript:;" data-reimbursement-id="' . $row->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                        }
                    }else{
                        $action .= '<a class="taskView d-flex align-items-center justify-content-center open-response text-'.$b->color.'" href="javascript:;" data-reimbursement-id="' . $row->id . '" data-act-id="' . $b->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                    }
                }
            }
            $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';
            $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-reimbursement-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
            if (is_null($row->reimbursements_recurring_id) && $row->status == 'draft' ) {
                $action .= '<a class="dropdown-item" href="'. route('reimbursements.show', $row->id).'?tab=detail' .'"><i class="fa fa-plus mr-2"></i>Add Detail</a>';
            }
            $action .= '<a href="' . route('reimbursements.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            // $action .= '<a href="' . route('reimbursements.show', [$row->id]) . '" class="dropdown-item openRightModal"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            if (is_null($row->reimbursements_recurring_id) && $row->status == 'draft') {
                if ($this->editReimbursementPermission == 'all' || ($this->editReimbursementPermission == 'added' && user()->id == $row->added_by)) {
                    if (is_null($row->project_id)) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('reimbursements.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    } else if (!is_null($row->project_id) && is_null($row->project_deleted_at)) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('reimbursements.edit', [$row->id]) . '">
                            <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    }
                }

                if ($this->deleteReimbursementPermission == 'all' || ($this->deleteReimbursementPermission == 'added' && user()->id == $row->added_by)) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-reimbursement-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                }
            }
            
            if($row->status == 'approved' || $row->status == 'paid'){
                $action .= '<a class="dropdown-item" target="_blank" href="' . route('reimbursements.download', [$row->id, 'view' => true]) . '"><i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '</a>';
            }

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
            if (is_null($row->reimbursements_recurring_id)) {
                return '<a href="' . route('reimbursements.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>';
            }

            return '<a href="' . route('reimbursements.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>
                <p class="mb-0"><span class="badge badge-primary"> ' . __('app.recurring') . ' </span></p>';
        });
        $datatables->addColumn('export_item_name', function ($row) {
            return $row->item_name;
        });
        $datatables->addColumn('client_name', function ($row) {
            return $row->company_name;
        });
        $datatables->addColumn('employee_name', function ($row) {
            return $row->user->name;
        });
        $datatables->editColumn('user_id', function ($row) {
            return view('components.employee', [
                'user' => $row->user
            ]);
        });
        $datatables->addColumn('urgency', function ($row) {
            return ucfirst($row->urgency);
        });
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
        $datatables->addColumn('status_export', function ($row) {
            return $row->status;
        });

        $datatables->editColumn(
            'purchase_date',
            function ($row) {
                if (!is_null($row->purchase_date)) {
                    return $row->purchase_date->translatedFormat($this->company->date_format);
                }
            }
        );
        $datatables->editColumn(
            'purchase_from',
            function ($row) {
                return !is_null($row->purchase_from) ? $row->purchase_from : '--';
            }
        );
        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->addIndexColumn();
        $datatables->removeColumn('currency_id');
        $datatables->removeColumn('name');
        $datatables->removeColumn('currency_symbol');
        $datatables->removeColumn('updated_at');
        $datatables->removeColumn('created_at');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatables, Reimbursement::CUSTOM_FIELD_MODEL);

        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check'], $customFieldColumns));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $approval = ReimbursementApproval::whereUser_id(user()->id);

        $model = Reimbursement::with('currency', 'user', 'user.employeeDetail', 'user.employeeDetail.designation', 'user.session')
            ->select('reimbursements.id','reimbursements.code', 'reimbursements.project_id', 'reimbursements.item_name', 'reimbursements.user_id', 'reimbursement_approval.user_id as approval_user_id', 'reimbursements.urgency', 'reimbursements.price', 'users.salutation', 'users.name', 'reimbursements.purchase_date', 'reimbursements.currency_id', 'currencies.currency_symbol', 'client_details.company_name', DB::raw("
            CASE 
                WHEN reimbursements.status <> 'on progress' THEN reimbursements.status
                ELSE concat('on progress ', SUBSTRING_INDEX( `approval_state`.`name`, ' ', 1 ))
            END AS status
        "),'reimbursements.state_id', 'reimbursements.purchase_from', 'reimbursements.reimbursements_recurring_id', 'designations.name as designation_name', 'reimbursements.added_by', 'projects.deleted_at as project_deleted_at', 'reimbursements.is_copied','approval_state.name as state_name')
            ->join('users', 'users.id', 'reimbursements.user_id')
            ->join('client_details', 'client_details.id', 'reimbursements.client_id')
            ->leftJoinSub($approval,'approval',function($join){
                $join->on('approval.header_id', '=', 'reimbursements.id');
            })
            ->leftJoin('reimbursement_approval', function($join) {
                $join->on('reimbursement_approval.header_id', '=', 'reimbursements.id');
                $join->on('reimbursement_approval.state_id', '=', 'reimbursements.state_id');
            })
            ->leftJoin('approval_state', function($join) {
                $join->on('approval_state.approval_id', '=', 'reimbursements.approval_id');
                $join->on('approval_state.state_id', '=', 'reimbursements.state_id');
            })
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id')
            ->leftJoin('projects', 'projects.id', 'reimbursements.project_id')
            ->join('currencies', 'currencies.id', 'reimbursements.currency_id');

        if (!$this->includeSoftDeletedProjects) {
            $model->whereNull('projects.deleted_at');
        }

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $model = $model->where(DB::raw('DATE(reimbursements.`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $model = $model->where(DB::raw('DATE(reimbursements.`purchase_date`)'), '<=', $endDate);
        }

        if ($request->status != 'all' && !is_null($request->status)) {
            $model = $model->where('reimbursements.status', '=', $request->status);
        }

        // if ($request->employee != 'all' && !is_null($request->employee)) {
        //     $model = $model->where('reimbursements.user_id', '=', $request->employee);
        // }

        if ($request->projectId != 'all' && !is_null($request->projectId)) {
            $model = $model->where('reimbursements.project_id', '=', $request->projectId);
        }

        if ($request->categoryId != 'all' && !is_null($request->categoryId)) {
            $model = $model->where('reimbursements.category_id', '=', $request->categoryId);
        }

        if ($request->stateId != 'all' && !is_null($request->stateId)) {
            $model = $model->where('reimbursement_approval.user_id', '=', $request->stateId);
            $model = $model->whereNotIn('reimbursements.state_id', ['1','2']);
        }

        if ($request->recurringID != '') {
            $model = $model->where('reimbursements.reimbursements_recurring_id', '=', $request->recurringID);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('reimbursements.code', 'like', '%' . request('searchText') . '%')
                    ->orWhere('reimbursements.item_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('users.name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('reimbursements.price', 'like', '%' . request('searchText') . '%');
            });
        }

        if ($this->viewReimbursementPermission == 'all') {
            $model->whereIn('reimbursements.status',['draft','active','on progress','approved','rejected','paid','done']);
        }else{
            if ($this->viewReimbursementPermission == 'added') {
                $model->where('reimbursements.added_by', user()->id);
            }
    
            if ($this->viewReimbursementPermission == 'owned') {
                $model->where('reimbursements.user_id', user()->id);
            }
    
            if ($this->viewReimbursementPermission == 'both') {
                $model->where(function ($query) {
                    $query->where('reimbursements.added_by', user()->id)
                        ->orWhere('reimbursements.user_id', user()->id)
                        ->orWhere('approval.user_id', user()->id);
                });
            }
            $model->whereRaw("CASE WHEN `approval`.`user_id` = `reimbursements`.`added_by` THEN  `reimbursements`.`status` is not null WHEN `approval`.`user_id` is null THEN `reimbursements`.`status` = 'draft' ELSE `reimbursements`.`status` <> 'draft' END");
        }
        return $model->groupBy('reimbursements.id');
    }

    public function html(){
        $dataTable = $this->setBuilder('reimbursements-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["reimbursements-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-reimbursement-status").selectpicker();
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
            __('app.id') => ['data' => 'id', 'name' => 'reimbursements.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.reimbursements.code') => ['data' => 'code', 'name' => 'code', 'exportable' => false, 'title' => __('modules.reimbursements.code')],
            __('modules.reimbursements.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'exportable' => false, 'title' => __('modules.reimbursements.itemName')],
            __('app.menu.itemName') => ['data' => 'export_item_name', 'name' => 'export_item_name', 'visible' => false, 'title' => __('modules.reimbursements.itemName')],
            __('app.client') => ['data' => 'client_name', 'name' => 'client_name', 'exportable' => false, 'title' =>  __('app.client')],
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],
            __('modules.reimbursements.urgency') => ['data' => 'urgency', 'name' => 'urgency', 'title' => __('modules.reimbursements.urgency')],
            __('app.menu.employees') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.menu.employees')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.employee')],
            __('modules.reimbursements.purchaseDate') => ['data' => 'purchase_date', 'name' => 'purchase_date', 'title' => __('modules.reimbursements.purchaseDate')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
            __('app.reimbursement') . ' ' . __('app.status') => ['data' => 'status_export', 'name' => 'status', 'visible' => false, 'title' => __('app.reimbursement')]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Reimbursement()), $action);
    }
}