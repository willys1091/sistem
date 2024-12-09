<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Reimbursement;
use App\Models\ReimbursementDetail;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class ReimbursementsDetailDataTable extends BaseDataTable{
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
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">
                        <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            if ($row->status == 'draft') {
                $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-reimbursement-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-reimbursement-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
            }
            $action .= '</div></div></div>';
            return $action;
        });
        $datatables->editColumn('category_name', function ($row) {
            return $row->category_name;
        });
        $datatables->addColumn('remarks', function ($row) {
            return $row->remarks;
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

        $customFieldColumns = CustomField::customFieldData($datatables, ReimbursementDetail::CUSTOM_FIELD_MODEL);
        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check'], $customFieldColumns));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $model = ReimbursementDetail::select('reimbursement_detail.id', 'reimbursements_category_detail.category_name', 'reimbursement_detail.remarks', 'reimbursement_detail.amount', 'reimbursement_detail.estdate',  'reimbursement_detail.created_at',  'reimbursements.status')
            ->join('reimbursements', 'reimbursements.id', 'reimbursement_detail.header_id')
            ->join('reimbursements_category_detail', 'reimbursements_category_detail.id', 'reimbursement_detail.category_id');
        return $model->where('reimbursement_detail.header_id', '=', $request->segment(3));
        //return $model;
    }

    public function html(){
        $dataTable = $this->setBuilder('reimbursementsdetail-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["reimbursementsdetail-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-reimbursement-status").selectpicker();
                }',
            ]);

        // if (canDataTableExport()) {
        //     $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        // }
        return $dataTable;
    }

    protected function getColumns(){
        $data = [
            '#' => ['data' => 'DT_RowIndex','title' =>'#', 'orderable' => false, 'searchable' => false, 'visible' => !showId()],
            __('app.id') => ['data' => 'id', 'name' => 'reimbursements.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.reimbursementCategory.categoryName') => ['data' => 'category_name', 'name' => 'category_name', 'title' => __('modules.reimbursementCategory.categoryName')],
            __('modules.reimbursements.remarks') => ['data' => 'remarks', 'name' => 'remarks', 'title' => __('modules.reimbursements.remarks')],
            __('app.price') => ['data' => 'amount', 'name' => 'amount', 'title' => __('app.price')],
            __('modules.reimbursements.purchaseDate') => ['data' => 'estdate', 'name' => 'estdate', 'title' => __('modules.reimbursements.purchaseDate')]
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