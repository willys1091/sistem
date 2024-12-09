<?php

namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use App\Traits\General;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseRequest;
use Modules\Purchase\Entities\PurchaseRequestApproval;
use Modules\Purchase\Entities\PurchaseRequestAct;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;

class PurchaseRequestDataTable extends BaseDataTable{
    use General;
    private $viewRequestPermission;
    private $deleteRequestPermission;
    private $editRequestPermission;

    public function __construct(){
        parent::__construct();
        $this->viewRequestPermission = user()->permission('view_purchase_request');
        $this->deleteRequestPermission = user()->permission('delete_purchase_request');
        $this->editRequestPermission = user()->permission('edit_purchase_request');
    }

    public function dataTable($query){
        return datatables()->eloquent($query)
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">';
                if($row->approval_user_id == user()->id){
                    $btn = $this->getApprovalAct('purchase_request',$row->id,$row->state_id);
                    foreach($btn as $b){
                        if($b->name == 'copy document' || $b->name == 'Copy Document'){
                            if($row->is_copied == '0'){
                                $action .= '<a class="taskView d-flex align-items-center justify-content-center copy text-'.$b->color.'" href="javascript:;" data-purchaserequest-id="' . $row->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                            }
                        }else{
                            $action .= '<a class="taskView d-flex align-items-center justify-content-center open-response text-'.$b->color.'" href="javascript:;" data-purchaserequest-id="' . $row->id . '" data-act-id="' . $b->id . '"><i class="fa '.$b->icon.' mr-2"></i></a>';
                        }
                    }
                }
                $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';
                $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-purchaserequest-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
                if ($row->status == 'draft') {
                    $action .= '<a class="dropdown-item" href="'. route('purchase-request.show', $row->id).'?tab=detail' .'"><i class="fa fa-plus mr-2"></i>Add Detail</a>';
                }
                $action .= '<a href="' . route('purchase-request.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

                // if ($this->viewRequestPermission == 'all'|| ($this->viewRequestPermission == 'added' && user()->id == $row->added_by)) {
                //     $action .= '<a class="dropdown-item" href="' . route('purchase_request.download', [$row->id]) . '">
                //                     <i class="fa fa-download mr-2"></i>' . trans('app.download') . '
                //                 </a>';
                //     $action .= '<a class="dropdown-item" target="_blank" href="' . route('purchase_request.download', [$row->id, 'view' => true]) . '">
                //                     <i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '
                //                 </a>';
                // }
                if ($row->status == 'draft') {
                    if ($this->editRequestPermission == 'all' || ($this->editRequestPermission == 'added' && user()->id == $row->added_by)) {
                        $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-request-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                    }
    
                    if ($this->deleteRequestPermission == 'all' || ($this->deleteRequestPermission == 'added' && user()->id == $row->added_by)) {
                        $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-request-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                    }
                }
                $action .= '</div>
                </div>
            </div>';
                return $action;
            })
            ->editColumn('status', function ($row) { 
                if ($row->status == 'draft') {
                    $class = 'text-gray';
                    $status = __('app.draft');
                }else if ($row->status == 'active') {
                    $class = 'text-light-blue';
                    $status = __('app.active');
                }else if ($row->status == 'on progress') {
                    $class = 'text-yellow';
                    $status = __('app.onProgress').' '. explode(" ",$row->state_name)[0];
                }else if ($row->status == 'approved') {
                    $class = 'text-light-green';
                    $status = __('app.approved');
                }else if ($row->status == 'paid') {
                    $class = 'text-blue';
                    $status = __('app.paid');
                }else if ($row->status == 'done') {
                    $class = 'text-dark-green';
                    $status = __('app.done');
                }else {
                    $class = 'text-red';
                    $status = __('app.rejected');
                }
                $status = '<i class="fa fa-circle mr-1 ' . $class . ' f-10"></i> ' . $status;
    
                return $status;
            })
            ->editColumn('code', function ($row) {
                return '<div class="media align-items-center">
                            <div class="media-body">
                        <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('purchase-request.show', [$row->id]) . '">' . ($row->code) . '</a></h5>
                        </div>
                      </div>';
            })
            ->editColumn('request_date', function ($row) {
                return !is_null($row->request_date) ? $row->request_date->translatedFormat($this->company->date_format) : '----';
            })
            ->editColumn('estimation_delivery_date', function ($row) {
                return !is_null($row->estimation_delivery_date) ? $row->estimation_delivery_date->translatedFormat($this->company->date_format) : '----';
            })
            ->editColumn('id', function ($row) {
                return $row->id;
            })
            ->editColumn('note', function ($row) {
                return $row->note;
            })
            ->addIndexColumn()
            ->smart(false)
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns([ 'action', 'code', 'status']);
    }

    public function query(PurchaseRequest $model){
        $request = $this->request();
        $approval = PurchaseRequestApproval::whereUser_id(user()->id);
        $model = $model->select('purchase_requests.request_date', 'purchase_requests.note','purchase_requests.estimation_delivery_date', 'purchase_requests.id', 'purchase_requests.code', 'purchase_requests.status', 'purchase_request_approval.user_id as approval_user_id','purchase_requests.state_id','purchase_requests.added_by')
                ->leftJoinSub($approval,'approval',function($join){
                    $join->on('approval.header_id', '=', 'purchase_requests.id');
                })
                ->leftJoin('purchase_request_approval', function($join) {
                    $join->on('purchase_request_approval.header_id', '=', 'purchase_requests.id');
                    $join->on('purchase_request_approval.state_id', '=', 'purchase_requests.state_id');
                })
                ->leftJoin('approval_state', function($join) {
                    $join->on('approval_state.approval_id', '=', 'purchase_requests.approval_id');
                    $join->on('approval_state.state_id', '=', 'purchase_requests.state_id');
                });
        if ($request->searchText != '') {
            $model = $model->where(function ($query) {
                $query->where('purchase_requests.code', 'like', '%' . request('searchText') . '%');
            });
        }

        $model->where(function ($query) {
            $query->where('purchase_requests.added_by', user()->id)
                ->orWhere('approval.user_id', user()->id);
        });
       
        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model = $model->where(DB::raw('DATE(purchase_requests.`request_date`)'), '>=', $startDate);
        }
        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model = $model->where(DB::raw('DATE(purchase_requests.`request_date`)'), '<=', $endDate);
        }
        if ($request->status != 'all' && !is_null($request->status)) {
            $model = $model->where('purchase_requests.status', '=', $request->status);
        }
        return $model->groupBy('purchase_requests.id');
    }

    public function html(){
        return $this->setBuilder('purchase-request-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["purchase-request-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".select-picker").selectpicker();
                }',
            ])
            ->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
    }

    protected function getColumns(){
        $data = [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'visible' => false, 'exportable' => false, 'title' => __('app.id')],
            __('app.requestNumber') => ['data' => 'code', 'name' => 'code', 'exportable' => true, 'title' => __('app.requestNumber')],
            __('app.note') => ['data' => 'note', 'name' => 'note', 'exportable' => true, 'title' => __('app.note')],
            __('app.requestDate') => ['data' => 'request_date', 'name' => 'request_date', 'title' => __('app.requestDate')],
            __('purchase::modules.purchaseRequest.estimationdDate') => ['data' => 'estimation_delivery_date', 'name' => 'estimation_delivery_date', 'title' => __('purchase::modules.purchaseRequest.estimationDate')],
            __('purchase::modules.purchaseRequest.status') => ['data' => 'status', 'name' => 'status', 'title' => __('purchase::modules.purchaseRequest.status')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
        return $data;
    }
}
