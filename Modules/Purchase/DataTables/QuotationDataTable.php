<?php

namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use App\Traits\General;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\Quotations;
use Modules\Purchase\Entities\QuotationApproval;
use Modules\Purchase\Entities\QuotationAct;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;

class QuotationDataTable extends BaseDataTable{
    use General;
    private $viewQuotationPermission;
    private $deleteQuotationPermission;
    private $editQuotationPermission;

    public function __construct(){
        parent::__construct();
        $this->viewQuotationPermission = user()->permission('view_quotation');
        $this->deleteQuotationPermission = user()->permission('delete_quotation');
        $this->editQuotationPermission = user()->permission('edit_quotation');
    }

    public function dataTable($query){
        return datatables()->eloquent($query)
        ->addColumn('action', function ($row) {
            $action = '<div class="task_view">';
            if($row->approval_user_id == user()->id){
                $btn = $this->getApprovalAct('quotation',$row->id,$row->state_id);
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
            $action .= '<a class="dropdown-item open-approval" href="javascript:;" data-quotation-id="' . $row->id . '"><i class="fa fa-user mr-2"></i>Approval</a>';
            if ($row->status == 'draft') {
                $action .= '<a class="dropdown-item" href="'. route('quotation.show', $row->id).'?tab=detail' .'"><i class="fa fa-plus mr-2"></i>Add Detail</a>';
            }
            $action .= '<a href="' . route('quotation.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

            if ($this->viewQuotationPermission == 'all'|| ($this->viewQuotationPermission == 'added' && user()->id == $row->added_by)) {
                $action .= '<a class="dropdown-item" href="' . route('quotation.download', [$row->id]) . '">
                                <i class="fa fa-download mr-2"></i>' . trans('app.download') . '
                            </a>';
                $action .= '<a class="dropdown-item" target="_blank" href="' . route('quotation.download', [$row->id, 'view' => true]) . '">
                                <i class="fa fa-eye mr-2"></i>' . trans('app.viewPdf') . '
                            </a>';
            }
            if ($row->status == 'draft') {
                if ($this->editQuotationPermission == 'all' || ($this->editQuotationPermission == 'added' && user()->id == $row->added_by)) {
                    $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-quotation-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                }

                if ($this->deleteQuotationPermission == 'all' || ($this->deleteQuotationPermission == 'added' && user()->id == $row->added_by)) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-quotation-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
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
                    <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('quotation.show', [$row->id]) . '">' . ($row->code) . '</a></h5>
                    </div>
                  </div>';
        })
        ->editColumn('expected_date', function ($row) {
            return !is_null($row->expected_date) ? $row->expected_date->translatedFormat($this->company->date_format) : '----';
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

    public function query(Quotations $model){
        $request = $this->request();
        $approval = QuotationApproval::whereUser_id(user()->id);
        $model = $model->select('quotations.id', 'quotations.code', 'quotations.expected_date', 'quotations.status', 'quotation_approval.user_id as approval_user_id','quotations.state_id','quotations.added_by')
                ->leftJoinSub($approval,'approval',function($join){
                    $join->on('approval.header_id', '=', 'quotations.id');
                })
                ->leftJoin('quotation_approval', function($join) {
                    $join->on('quotation_approval.header_id', '=', 'quotations.id');
                    $join->on('quotation_approval.state_id', '=', 'quotations.state_id');
                })
                ->leftJoin('approval_state', function($join) {
                    $join->on('approval_state.approval_id', '=', 'quotations.approval_id');
                    $join->on('approval_state.state_id', '=', 'quotations.state_id');
                });
        if ($request->searchText != '') {
            $model = $model->where(function ($query) {
                $query->where('quotations.code', 'like', '%' . request('searchText') . '%');
            });
        }
        $model->where(function ($query) {
            $query->where('quotations.added_by', user()->id)
                ->orWhere('approval.user_id', user()->id);
        });
        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model = $model->where(DB::raw('DATE(quotations.`expected_date`)'), '>=', $startDate);
        }
        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model = $model->where(DB::raw('DATE(quotations.`expected_date`)'), '<=', $endDate);
        }
        if ($request->status != 'all' && !is_null($request->status)) {
            $model = $model->where('quotations.status', '=', $request->status);
        }
        return $model->groupBy('quotations.id');
    }

    public function html(){
        return $this->setBuilder('quotation-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["quotation-table"].buttons().container()
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
            __('app.quotationNumber') => ['data' => 'code', 'name' => 'code', 'exportable' => true, 'title' => __('app.quotationNumber')],
            __('app.requestNumber') => ['data' => 'request_number', 'name' => 'request_number', 'exportable' => true, 'title' => __('app.requestNumber')],
            __('app.quotationDate') => ['data' => 'quotation_date', 'name' => 'quotation_date', 'title' => __('app.quotationDate')],
            __('purchase::modules.quotation.expectedDate') => ['data' => 'expected_delivery_date', 'name' => 'expected_delivery_date', 'title' => __('purchase::modules.quotation.expectedDate')],
            __('purchase::modules.quotation.quotationStatus') => ['data' => 'quotation_status', 'name' => 'quotation_status', 'title' => __('purchase::modules.quotation.quotationStatus')],
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