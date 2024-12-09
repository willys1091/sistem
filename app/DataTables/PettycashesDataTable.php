<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Pettycash;
use App\Models\PettycashAct;
use App\Models\PettycashApproval;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class PettycashesDataTable extends BaseDataTable{
    use General;
    private $editPettycashPermission;
    private $deletePettycashPermission;
    private $viewPettycashPermission;
    private $approvePettycashPermission;
    private $includeSoftDeletedProjects;

    public function __construct($includeSoftDeletedProjects = false){
        parent::__construct();
        $this->editPettycashPermission = user()->permission('edit_pettycashes');
        $this->deletePettycashPermission = user()->permission('delete_pettycashes');
        $this->viewPettycashPermission = user()->permission('view_pettycashes');
        $this->approvePettycashPermission = user()->permission('approve_pettycashes');
        $this->includeSoftDeletedProjects = $includeSoftDeletedProjects;
    }

    public function dataTable($query){
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', fn($row) => $this->checkBox($row));
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
            if($row->approval_user_id == user()->id && $row->price > 0){
                $btn = $this->getApprovalAct('pettycash',$row->id,$row->state_id);
                foreach($btn as $b){
                    if($b->name == 'copy document' || $b->name == 'Copy Document'){
                        if($row->is_copied == '0'){
                            $action .= '<a class="taskView d-flex align-items-center justify-content-center copy text-'.$b->color.'" href="javascript:;" data-pettycash-id="' . $row->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                        }
                    }else{
                        $action .= '<a class="taskView d-flex align-items-center justify-content-center open-response text-'.$b->color.'" href="javascript:;" data-pettycash-id="' . $row->id . '" data-act-id="' . $b->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                    }
                }
            }
            $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';
            $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-pettycash-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
            if (is_null($row->pettycashes_recurring_id) && $row->status == 'draft' && $row->is_detail=='1') {
                $action .= '<a class="dropdown-item" href="'. route('pettycashes.show', $row->id).'?tab=detail' .'"><i class="fa fa-plus mr-2"></i>Add Detail</a>';
            }
            $action .= '<a href="' . route('pettycashes.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            if (is_null($row->pettycashes_recurring_id) && $row->status == 'draft') {
                if ($this->editPettycashPermission == 'all' || ($this->editPettycashPermission == 'added' && user()->id == $row->added_by)) {
                    if (is_null($row->project_id)) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('pettycashes.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    } else if (!is_null($row->project_id) && is_null($row->project_deleted_at)) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('pettycashes.edit', [$row->id]) . '">
                            <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
                    }
                }

                if ($this->deletePettycashPermission == 'all' || ($this->deletePettycashPermission == 'added' && user()->id == $row->added_by)) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-pettycash-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                }
            }
            

            if($row->status == 'approved'){
                $action .= '<a class="dropdown-item" target="_blank" href="' . route('pettycashes.download', [$row->id, 'view' => true]) . '"><i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '</a>';
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
            if (is_null($row->pettycashes_recurring_id)) {
                return '<a href="' . route('pettycashes.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>';
            }

            return '<a href="' . route('pettycashes.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>
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

        $datatables->editColumn('purchase_date',function ($row) {
            if (!is_null($row->purchase_date)) {
                return $row->purchase_date->translatedFormat($this->company->date_format);
            }
        });

        $datatables->editColumn('purchase_from', function ($row) {
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
        $customFieldColumns = CustomField::customFieldData($datatables, Pettycash::CUSTOM_FIELD_MODEL);

        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check'], $customFieldColumns));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $approval = PettycashApproval::whereUser_id(user()->id);

        $model = Pettycash::with('currency', 'user', 'user.employeeDetail', 'user.employeeDetail.designation', 'user.session')
            ->select('pettycashes.id','pettycashes.code', 'pettycashes.project_id', 'pettycashes.item_name', 'pettycashes.user_id', 'pettycash_approval.user_id as approval_user_id', 'pettycashes.urgency', 'pettycashes.price', 'users.salutation', 'users.name', 'pettycashes.purchase_date', 'pettycashes.currency_id', 'currencies.currency_symbol', 'client_details.company_name', DB::raw("
            CASE 
                WHEN pettycashes.status <> 'on progress' THEN pettycashes.status
                ELSE concat('on progress ', SUBSTRING_INDEX( `approval_state`.`name`, ' ', 1 ))
            END AS status
        "),'pettycashes.state_id', 'pettycashes.purchase_from', 'pettycashes.pettycashes_recurring_id', 'designations.name as designation_name', 'pettycashes.added_by', 'projects.deleted_at as project_deleted_at', 'pettycashes.is_copied','approval_state.name as state_name', 'pettycashes.is_detail')
            ->join('users', 'users.id', 'pettycashes.user_id')
            ->join('client_details', 'client_details.id', 'pettycashes.client_id')
            ->leftJoinSub($approval,'approval',function($join){
                $join->on('approval.header_id', '=', 'pettycashes.id');
            })
            ->leftJoin('pettycash_approval', function($join) {
                $join->on('pettycash_approval.header_id', '=', 'pettycashes.id');
                $join->on('pettycash_approval.state_id', '=', 'pettycashes.state_id');
            })
            ->leftJoin('approval_state', function($join) {
                $join->on('approval_state.approval_id', '=', 'pettycashes.approval_id');
                $join->on('approval_state.state_id', '=', 'pettycashes.state_id');
            })
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id')
            ->leftJoin('projects', 'projects.id', 'pettycashes.project_id')
            ->join('currencies', 'currencies.id', 'pettycashes.currency_id');

        if (!$this->includeSoftDeletedProjects) {
            $model->whereNull('projects.deleted_at');
        }

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $model = $model->where(DB::raw('DATE(pettycashes.`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $model = $model->where(DB::raw('DATE(pettycashes.`purchase_date`)'), '<=', $endDate);
        }

        if ($request->status != 'all' && !is_null($request->status)) {
            $model = $model->where('pettycashes.status', '=', $request->status);
        }

        // if ($request->employee != 'all' && !is_null($request->employee)) {
        //     $model = $model->where('pettycashes.user_id', '=', $request->employee);
        // }

        if ($request->projectId != 'all' && !is_null($request->projectId)) {
            $model = $model->where('pettycashes.project_id', '=', $request->projectId);
        }

        if ($request->categoryId != 'all' && !is_null($request->categoryId)) {
            $model = $model->where('pettycashes.category_id', '=', $request->categoryId);
        }

        if ($request->recurringID != '') {
            $model = $model->where('pettycashes.pettycashes_recurring_id', '=', $request->recurringID);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('pettycashes.code', 'like', '%' . request('searchText') . '%')
                    ->orWhere('pettycashes.item_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('users.name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('pettycashes.price', 'like', '%' . request('searchText') . '%');
            });
        }

        if ($this->viewPettycashPermission == 'all') {
            $model->whereIn('pettycashes.status',['draft','active','on progress','approved','declined']);
        }else{
            if ($this->viewPettycashPermission == 'added') {
                $model->where('pettycashes.added_by', user()->id);
            }
    
            if ($this->viewPettycashPermission == 'owned') {
                $model->where('pettycashes.user_id', user()->id);
            }
    
            if ($this->viewPettycashPermission == 'both') {
                $model->where(function ($query) {
                    $query->where('pettycashes.added_by', user()->id)
                        ->orWhere('pettycashes.user_id', user()->id)
                        ->orWhere('approval.user_id', user()->id);
                });
            }
            $model->whereRaw("CASE WHEN `approval`.`user_id` = `pettycashes`.`added_by` THEN  `pettycashes`.`status` is not null WHEN `approval`.`user_id` is null THEN `pettycashes`.`status` = 'draft' ELSE `pettycashes`.`status` <> 'draft' END");
        }
        
        return $model->groupBy('pettycashes.id');
    }

    public function html(){
        $dataTable = $this->setBuilder('pettycashes-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["pettycashes-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-pettycash-status").selectpicker();
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
            __('app.id') => ['data' => 'id', 'name' => 'pettycashes.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.pettycashes.code') => ['data' => 'code', 'name' => 'code', 'exportable' => false, 'title' => __('modules.pettycashes.code')],
            __('modules.pettycashes.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'exportable' => false, 'title' => __('modules.pettycashes.itemName')],
            __('app.menu.itemName') => ['data' => 'export_item_name', 'name' => 'export_item_name', 'visible' => false, 'title' => __('modules.pettycashes.itemName')],
            __('app.client') => ['data' => 'client_name', 'name' => 'client_name', 'exportable' => false, 'title' =>  __('app.client')],
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],
            __('app.menu.employees') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.menu.employees')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.employee')],
            __('modules.pettycashes.purchaseDate') => ['data' => 'purchase_date', 'name' => 'purchase_date', 'title' => __('modules.pettycashes.purchaseDate')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
            __('app.pettycash') . ' ' . __('app.status') => ['data' => 'status_export', 'name' => 'status', 'visible' => false, 'title' => __('app.pettycash')]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Pettycash()), $action);
    }
}