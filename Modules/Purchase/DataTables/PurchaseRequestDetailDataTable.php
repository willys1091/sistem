<?php

namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use App\Traits\General;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseRequest;
use Modules\Purchase\Entities\PurchaseRequestApproval;
use Modules\Purchase\Entities\PurchaseRequestAct;
use Modules\Purchase\Entities\PurchaseRequestItem;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;

class PurchaseRequestDetailDataTable extends BaseDataTable{
    use General;
    private $editPurchaseRequestPermission;
    private $deletePurchaseRequestPermission;
    private $viewPurchaseRequestPermission;
    private $approvePurchaseRequestPermission;

    public function __construct($includeSoftDeletedProjects = false){
        parent::__construct();
        $this->editPurchaseRequestPermission = user()->permission('edit_purchase_request');
        $this->deletePurchaseRequestPermission = user()->permission('delete_purchase_request');
        $this->viewPurchaseRequestPermission = user()->permission('view_purchase_request');
        $this->approvePurchaseRequestPermission = user()->permission('approve_purchase_request');
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
                            $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-request-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                            $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-request-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
                        }
            $action .= '</div></div></div>';
            return $action;
        });
        $datatables->editColumn('item_name', function ($row) {
            return $row->item_name;
        });
        $datatables->editColumn('amount', function ($row) {
            return $row->total_amount;
        });
        $datatables->editColumn('qty', function ($row) {
            return $row->quantity;
        });
        $datatables->editColumn('remarks', function ($row) {
            return $row->remarks;
        });
       
        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->addIndexColumn();
        $datatables->removeColumn('updated_at');
        $datatables->removeColumn('created_at');

        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'check']));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $model = PurchaseRequestItem::select('purchase_request_items.id', 'purchase_request_items.header_id', 'purchase_request_items.quantity','products.name as item_name','purchase_request_items.item_summary','purchase_request_items.unit_id','purchase_request_items.uom','purchase_request_items.item_summary as remarks','purchase_requests.status' )
            ->join('purchase_requests', 'purchase_requests.id', 'purchase_request_items.header_id')
            ->Join('products', function($join) {
                $join->on('products.id', '=', 'purchase_request_items.product_id');
                $join->on('products.company_id', '=', 'purchase_requests.company_id');
            });
        return $model->where('purchase_requests.id', '=', $request->segment(3));
    }

    public function html(){
        return $this->setBuilder('purchaserequestdetail-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["purchaserequestdetail-table"].buttons().container()
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
            '#' => ['data' => 'DT_RowIndex','title' =>'#', 'orderable' => false, 'searchable' => false, 'visible' => !showId()],
            __('app.id') => ['data' => 'id', 'name' => 'purchase_request_items.id', 'title' => __('app.id'),'visible' => showId()],
            __('purchase::modules.purchaseRequest.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'title' => __('purchase::modules.purchaseRequest.itemName')],
            __('purchase::modules.purchaseRequest.quantity') => ['data' => 'qty', 'name' => 'qty', 'title' => __('purchase::modules.purchaseRequest.quantity')],
            __('purchase::modules.purchaseRequest.uom') => ['data' => 'uom', 'name' => 'uom', 'title' => __('purchase::modules.purchaseRequest.uom')],
            __('purchase::modules.purchaseRequest.remarks') => ['data' => 'remarks', 'name' => 'remarks', 'title' => __('purchase::modules.purchaseRequest.remarks')],
            
            //__('app.price') => ['data' => 'amount', 'name' => 'amount', 'title' => __('app.price')],
            //__('modules.expenses.purchaseDate') => ['data' => 'estdate', 'name' => 'estdate', 'title' => __('modules.expenses.purchaseDate')],
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