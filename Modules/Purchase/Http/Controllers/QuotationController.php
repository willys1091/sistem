<?php

namespace Modules\Purchase\Http\Controllers;

use Carbon\Carbon;
use App\Traits\General;
use App\Models\Tax;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Product;
use App\Models\Currency;
use App\Models\UnitType;
use App\Models\BankAccount;
use App\Models\Approval;
use App\Models\ApprovalAct;
use App\Models\ApprovalState;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Modules\Purchase\Entities\PurchaseItem;
use Illuminate\Contracts\Support\Renderable;
use App\Http\Controllers\AccountBaseController;
use Modules\Purchase\Events\NewQuotationEvent;
use Modules\Purchase\Entities\PurchaseRequest;
use Modules\Purchase\Entities\Quotations;
use Modules\Purchase\Entities\QuotationDetail;
use Modules\Purchase\Entities\QuotationApproval;
use Modules\Purchase\Entities\QuotationAct;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Entities\QuotationHistory;
use Modules\Purchase\DataTables\QuotationDataTable;
use Modules\Purchase\DataTables\QuotationDetailDataTable;
use Modules\Purchase\Http\Requests\Quotation\StoreRequest;
use Modules\Purchase\Http\Requests\Quotation\StoreDetailRequest;

class QuotationController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'purchase::app.menu.quotation';
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }

    public function index(QuotationDataTable $dataTable){
        $viewPermission = user()->permission('view_quotation');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'purchase::app.menu.quotation';
        return $dataTable->render('purchase::quotation.index', $this->data);
    }

    public function create(){
        $this->pageTitle = __('purchase::app.menu.quotation');
        $this->request = PurchaseRequest::whereCompany_idAndStatus(company()->id,'approved')->get();
        return view('purchase::quotation.ajax.create', $this->data);
    }

    public function store(StoreRequest $request){
        $quotation = new Quotations();
        company()?$quotation->company_id = company()->id : '';
        $quotation->code = $this->generateCode();
        $quotation->expected_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');
        $quotation->request_id = $request->request_id;
        $quotation->status = 'draft';
        $quotation->added_by = user()->id;
        $quotation->save();

        $this->generateApproval($quotation->id);
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('quotation.index'), 'quotationID' => $quotation->id]);
    }

    public function show($id){
        $this->request = PurchaseRequest::findOrFail($id);

        $this->viewPermission = user()->permission('view_quotation');
        $this->deletePermission = user()->permission('delete_quotation');
        $this->addPermission = user()->permission('add_quotation');


        $this->pageTitle = $this->request->code;

        $tab = request('tab');

        switch ($tab) {
            case 'detail':
                return $this->detail($id);
                break;
            default:
                $this->view = 'purchase::quotation.ajax.overview';
                break;
        }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }
        $this->activeTab = $tab ?: 'overview';
        return view('purchase::quotation.show', $this->data);
    }

    private function detail($id){
        $dataTable = new QuotationDetailDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'purchase::quotation.ajax.detail';
        return $dataTable->render('purchase::quotation.show', $this->data);
    }

    public function edit($id){
        $this->pageTitle = __('purchase::app.menu.quotation');
        $this->request = Quotations::findOrFail($id);
        return view('purchase::quotation.ajax.edit', $this->data);
    }

    public function update(StoreRequest $request, $id){
        $pRequest = Quotations::findOrFail($id);
        $pRequest->request_date = Carbon::createFromFormat($this->company->date_format, $request->request_date)->format('Y-m-d');
        $pRequest->note = trim_editor($request->note);
        $pRequest->estimation_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->estimation_date)->format('Y-m-d');
        $pRequest->save();

        $this->generateApproval($pRequest->id);

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('quotation.index'), 'requestID' => $pRequest->id]);
    }

    public function destroy($id){
        $this->purchaseRequest = Quotations::findOrFail($id);
        $this->deletePermission = user()->permission('delete_quotation');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->quotation->added_by == user()->id)));
        Quotations::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }
}