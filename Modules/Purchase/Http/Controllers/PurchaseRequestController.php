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
use Modules\Purchase\Events\NewPurchaseRequestEvent;
use Modules\Purchase\Entities\PurchaseRequest;
use Modules\Purchase\Entities\PurchaseRequestItem;
use Modules\Purchase\Entities\PurchaseRequestApproval;
use Modules\Purchase\Entities\PurchaseRequestAct;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Entities\PurchaseRequestHistory;
use Modules\Purchase\DataTables\PurchaseRequestDataTable;
use Modules\Purchase\DataTables\PurchaseRequestDetailDataTable;
use Modules\Purchase\Http\Requests\PurchaseRequest\StoreRequest;
use Modules\Purchase\Http\Requests\PurchaseRequest\StoreDetailRequest;

class PurchaseRequestController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'purchase::app.menu.purchaseRequest';
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }

    public function index(PurchaseRequestDataTable $dataTable){
        $viewPermission = user()->permission('view_purchase_request');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'purchase::app.menu.purchaseRequest';
        return $dataTable->render('purchase::purchase-request.index', $this->data);
    }

    public function create(){
        $this->pageTitle = __('purchase::app.menu.purchaseRequest');
        return view('purchase::purchase-request.ajax.create', $this->data);
    }

    public function store(StoreRequest $request){
        $pRequest = new PurchaseRequest();
        company()?$pRequest->company_id = company()->id : '';
        $pRequest->code = $this->generateCode();
        $pRequest->request_date = Carbon::createFromFormat($this->company->date_format, $request->request_date)->format('Y-m-d');
        $pRequest->note = trim_editor($request->note);
        $pRequest->estimation_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->estimation_date)->format('Y-m-d');
        $pRequest->status = 'draft';
        $pRequest->added_by = user()->id;
        $pRequest->save();

        $this->generateApproval($pRequest->id);

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-request.index'), 'requestID' => $pRequest->id]);
    }

    public function show($id){
        $this->request = PurchaseRequest::findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_request');
        $this->deletePermission = user()->permission('delete_purchase_request');
        $this->addPermission = user()->permission('add_purchase_request');

        // abort_403(!(
        //     $this->viewPermission == 'all'
        //     || ($this->viewPermission == 'added' && $this->request->added_by == user()->id)
        //     || ($this->viewPermission == 'owned' && $this->request->send_status)
        //     || ($this->viewPermission == 'both' && ($this->request->added_by == user()->id))
        // ));

        $this->pageTitle = $this->request->code;

        $tab = request('tab');

        switch ($tab) {
            case 'detail':
                return $this->detail($id);
                break;
            default:
                $this->view = 'purchase::purchase-request.ajax.overview';
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }
        $this->activeTab = $tab ?: 'overview';
        return view('purchase::purchase-request.show', $this->data);
    }

    private function detail($id){
        $dataTable = new PurchaseRequestDetailDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'purchase::purchase-request.ajax.detail';
        return $dataTable->render('purchase::purchase-request.show', $this->data);
    }

    public function edit($id){
        $this->pageTitle = __('purchase::app.menu.purchaseRequest');
        $this->request = PurchaseRequest::findOrFail($id);
        return view('purchase::purchase-request.ajax.edit', $this->data);
    }

    public function update(StoreRequest $request, $id){
        $pRequest = PurchaseRequest::findOrFail($id);
        $pRequest->request_date = Carbon::createFromFormat($this->company->date_format, $request->request_date)->format('Y-m-d');
        $pRequest->note = trim_editor($request->note);
        $pRequest->estimation_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->estimation_date)->format('Y-m-d');
        $pRequest->save();

        $this->generateApproval($pRequest->id);

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-request.index'), 'requestID' => $pRequest->id]);
    }

    public function destroy($id){
        $this->purchaseRequest = PurchaseRequest::findOrFail($id);
        $this->deletePermission = user()->permission('delete_purchase_request');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->purchaseRequest->added_by == user()->id)));
        PurchaseRequest::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function detailCreate(){
        $this->requestId = request('id');
        $this->request = PurchaseRequest::findOrFail($this->requestId);
        $this->unitType = UnitType::whereCompany_id(user()->company_id)->get();
        $this->product = Product::whereCompany_id(user()->company_id)->get();
        $this->redirectUrl = request('redirectUrl');
        return view('purchase::purchase-request.ajax.createDetail', $this->data);
    }

    public function detailStore(StoreDetailRequest $request){
        $detail = new PurchaseRequestItem();
        $detail->header_id = $request->request_id;
        $detail->product_id = $request->product_id;
        $detail->item_summary = $request->remarks;
        $detail->quantity = $request->qty;
        $detail->uom = $request->unit_id;
        $detail->save();
        $this->generateApproval($request->request_id);
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-request.show', [$request->request_id,'tab=detail'])]);
    }

    public function detailedit($id){
        $this->request = PurchaseRequest::findOrFail($id);
        $this->detail = PurchaseRequestItem::findorfail($id);
        $this->product = Product::whereCompany_id(user()->company_id)->get();
        $this->productDetail = Product::findorfail($this->detail->product_id);
        $this->unitType = UnitType::findorfail( $this->productDetail->unit_id);
        $this->redirectUrl = request('redirectUrl');
        return view('purchase::purchase-request.ajax.editDetail', $this->data);
    }

    public function detailupdate(StoreDetailRequest $request,$id){
        $detail = PurchaseRequestItem::findorfail($id);
        $detail->product_id = $request->product_id;
        $detail->item_summary = $request->remarks;
        $detail->quantity = $request->qty;
        $detail->uom = $request->unit_id;
        $detail->save();
        $this->generateApproval($request->request_id);
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-request.show', [$detail->header_id,'tab=detail'])]);
    }

    public function detailDelete($id){
        $detail = PurchaseRequestItem::whereId($id)->first();
        PurchaseRequestItem::destroy($id);
        $this->generateApproval($detail->header_id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function approvalList($id){
        $total = PurchaseRequestApproval::whereHeader_id($id)->count();
        if($total==1){
            $this->approval = PurchaseRequestApproval::whereHeader_id($id)->get();
        }elseif($total>1){
            $this->approval = PurchaseRequestApproval::whereHeader_id($id)->whereNotIn('id',PurchaseRequestApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        }
        return view('purchase::purchase-request.approval', $this->data);
    }

    public function checkApproval(Request $request){
        $act = PurchaseRequestAct::findorfail($request->actid);
        if(PurchaseRequestApproval::whereId($act->approval_id)->value('user_id') == user()->id){
            return Reply::success('Have Access to Respond');
        }else{
            return Reply::error('You Don\'t Have Access to Respond');
        }
    }

    public function response($id,$actid){
        $this->header = PurchaseRequest::findorfail($id);
        $this->act = $act = PurchaseRequestAct::findorfail($actid);
        return view('purchase::purchase-request.response', $this->data);
    }

    public function responseAction(Request $request){
        $act = PurchaseRequestAct::findorfail($request->act_id);
        PurchaseRequestApproval::whereId($act->approval_id)->update(['status'=>$act->apv_status,'remarks'=>$request->description,'approval_date'=>date('Y-m-d H:i:s')]);
        PurchaseRequest::whereId($request->header_id)->update(['status'=>$act->status,'state_id'=>$act->next_state]);
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-request.index')]);
    }

    public function copy(Request $request){

    }

    public function getUom(Request $request){
        $product = Product::findorfail($request->product_id);
        $uom = UnitType::findorfail($product->unit_id);
        echo '<label class="f-14 text-dark-grey mb-12 mt-3" data-label="true" for="unit_id">UOM &nbsp;<sup class="f-14 mr-1">*</sup></label>';
        echo '<select class="form-control height-35 select-picker" name="unit_id" id="unit_id" data-live-search="true">';
        echo '<option value="">--</option>';
        echo '<option value="'.$uom->unit_type.'">'.$uom->unit_type.'</option>';
        echo (($uom->unit_type2==$uom->unit_type)?'':'<option value="'.$uom->unit_type2.'">'.$uom->unit_type2.'</option>');
        echo (($uom->unit_type3==$uom->unit_type)||($uom->unit_type3==$uom->unit_type2)?'':'<option value="'.$uom->unit_type3.'">'.$uom->unit_type3.'</option>');
        echo '</select>';
    }

    private function generateCode(){
        $last = PurchaseRequest::whereRaw('year(created_at)="'.date('Y').'"')->whereRaw('month(created_at)="'.date('m').'"')->latest('id');
        if($last->count() > 0){
            $data = $last->first();
            return "PR".date('ymd').str_pad(((int)substr($data->code,8,4) +1), 4, '0', STR_PAD_LEFT);
        }else{
            return "PR".date('ymd')."0001";
        }
    }

    protected function generateApproval($id){
        $apvid = Approval::whereNameAndCompany_id('purchase_request',user()->company_id)->value('id');
        $expapv = PurchaseRequest::whereId($id)->value('approval_id');
        if($apvid <> $expapv){
            PurchaseRequestApproval::Where('header_id',$id)->delete();
            
            if(PurchaseRequestApproval::count()>0){
                $expapv = PurchaseRequestApproval::latest('id')->first();
                $expact = PurchaseRequestAct::latest('id')->first();
                DB::statement('ALTER TABLE purchase_request_approval AUTO_INCREMENT = '.$expapv->id.';');
                DB::statement('ALTER TABLE purchase_request_act AUTO_INCREMENT = '.$expact->id.';');
            }
          
            PurchaseRequest::whereId($id)->update(['approval_id' => $apvid ]);
            $state = ApprovalState::whereApproval_id($apvid)->get();

            foreach($state as $s){
                if(!filter_var($s->users, FILTER_VALIDATE_INT)){
                    $user = $this->getUserApproval($s->users,user()->id);
                }else{
                    $user = $s->users;
                }
                $apv = new PurchaseRequestApproval;
                $apv->header_id = $id;
                $apv->state_id = $s->state_id;
                $apv->user_id = $user;
                $apv->save();

                $apvact = ApprovalAct::whereState_idAndApproval_id($s->state_id,$apvid)->get();

                foreach($apvact as $aa){
                    $act = new PurchaseRequestAct;
                    $act->approval_id = $apv->id;
                    $act->name = $aa->name;
                    $act->next_state = $aa->next_state;
                    $act->status = $aa->status;
                    $act->apv_status = $aa->apv_status;
                    $act->icon = $aa->icon;
                    $act->color = $aa->color;
                    $act->save();
                }
            }
        }
    }
}