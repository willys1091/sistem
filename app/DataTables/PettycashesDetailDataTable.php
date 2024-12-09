<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Pettycash;
use App\Models\PettycashDetail;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class PettycashesDetailDataTable extends BaseDataTable{
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
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">
                        <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            if ($row->status == 'draft') {
                $action .= '<a class="dropdown-item open-edit" href="javascript:;" data-pettycash-id="' . $row->id . '"><i class="fa fa-edit mr-2"></i>'.trans('app.edit').'</a>';
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-pettycash-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . trans('app.delete') . '</a>';
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

        //$customFieldColumns = CustomField::customFieldData($datatables, PettycashDetail::CUSTOM_FIELD_MODEL);
        $datatables->rawColumns(array_merge(['action', 'status', 'user_id', 'item_name', 'check']));

        return $datatables;
    }

    public function query(){
        $request = $this->request();
        $model = PettycashDetail::select('pettycash_detail.id', 'pettycashes_category_detail.category_name', 'pettycash_detail.remarks', 'pettycash_detail.amount', 'pettycash_detail.estdate',  'pettycash_detail.created_at',  'pettycashes.status')
            ->join('pettycashes', 'pettycashes.id', 'pettycash_detail.header_id')
            ->join('pettycashes_category_detail', 'pettycashes_category_detail.id', 'pettycash_detail.category_id');
        return $model->where('pettycash_detail.header_id', '=', $request->segment(3));
        //return $model;
    }

    public function html(){
        $dataTable = $this->setBuilder('pettycashesdetail-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["pettycashesdetail-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".change-pettycash-status").selectpicker();
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
            __('app.id') => ['data' => 'id', 'name' => 'pettycashes.id', 'title' => __('app.id'),'visible' => showId()],
            __('modules.pettycashCategory.categoryName') => ['data' => 'category_name', 'name' => 'category_name', 'title' => __('modules.pettycashCategory.categoryName')],
            __('modules.pettycashes.remarks') => ['data' => 'remarks', 'name' => 'remarks', 'title' => __('modules.pettycashes.remarks')],
            __('app.price') => ['data' => 'amount', 'name' => 'amount', 'title' => __('app.price')],
            __('modules.pettycashes.purchaseDate') => ['data' => 'estdate', 'name' => 'estdate', 'title' => __('modules.pettycashes.purchaseDate')]
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