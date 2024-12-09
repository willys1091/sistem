<?php

namespace App\Http\Controllers;

use App\DataTables\SettlementsDataTable;
use App\DataTables\SettlementsDetailDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Settlements\StoreSettlement;
use App\Http\Requests\Settlements\StoreSettlementDetail;
use App\Models\User;
use App\Models\Currency;
use App\Models\Approval;
use App\Models\ApprovalAct;
use App\Models\ApprovalState;
use App\Models\Expense;
use App\Models\ExpenseDetail;
use App\Models\ExpensesCategoryDetail;
use App\Models\Settlement;
use App\Models\SettlementDetail;
use App\Models\SettlementAct;
use App\Models\SettlementApproval;
use App\Traits\General;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementController extends AccountBaseController{
    use General;
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.settlements';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('settlements', $this->user->modules));
            return $next($request);
        });
    }

    public function index(SettlementsDataTable $dataTable){
        $viewPermission = user()->permission('view_settlements');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->employees = User::allEmployees(null, true);
            $this->approvals = ApprovalState::select('approval_state.users','users.name')->join('approval','approval.id','approval_state.approval_id')->join('users','users.id','approval_state.users')
                                ->where('approval_state.state_id','>','2')->where('approval.name','settlement')->where('approval.company_id',user()->company_id)->where('approval_state.users','<>','author')->groupby('approval_state.users')->get();
        }
        return $dataTable->render('settlements.index', $this->data);
    }

    private function detail($id){
        $dataTable = new SettlementsDetailDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'settlements.ajax.detail';
        return $dataTable->render('settlements.show', $this->data);
    }

    public function changeStatus(Request $request){
        abort_403(user()->permission('approve_settlements') != 'all');

        // $expenseId = $request->expenseId;
        // $status = $request->status;
        // $expense = Expense::findOrFail($expenseId);
        // $expense->status = $status;
        // $expense->save();
        // return Reply::success(__('messages.updateSuccess'));
    }

    public function show($id){
        $this->viewPermission = user()->permission('view_settlements');
        $this->editSettlmentPermission = user()->permission('edit_settlements');
        $this->deleteSettlementPermission = user()->permission('delete_settlements');

        $this->settlement = Settlement::findOrFail($id);

        $this->pageTitle = $this->settlement->expense->item_name;

        $tab = request('tab');

        switch ($tab) {
            case 'detail':
                return $this->detail($id);
                break;
            default:
                $this->view = 'settlements.ajax.overview';
                break;
            }
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('settlements.show', $this->data);
    }

    public function create(){
        $this->addPermission = user()->permission('add_settlements');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->expenses = Expense::whereUser_idAndIs_settlement(user()->id,0)->whereIn('status',['paid','done'])->get();
        return view('settlements.ajax.create', $this->data);
    }

    public function store(StoreSettlement $request){
        $expense = Expense::findorfail($request->expense_id);
        $expenseDetail = ExpenseDetail::whereHeader_id($request->expense_id)->get();
        $settlement = new settlement;
        $settlement->code = $this->generateCode($expense->urgency=='normal'?'U':'U',$expense->is_detail=='1'?'K':'N');
        $settlement->expense_id = $request->expense_id;
        $settlement->user_id = user()->id;
        $settlement->save();

        if(count($expenseDetail)>0){
            foreach($expenseDetail as $ed){
                $detail = new SettlementDetail;
                $detail->header_id = $settlement->id;
                $detail->category_id = $ed->category_id;
                $detail->amount = 0;
                $detail->estdate = $ed->estdate;
                $detail->reff_id = $ed->id;
                $detail->save();
            }
        }
        
        $this->generateApproval($settlement->id,$settlement->expense->urgency=='normal'?0:0,$settlement->expense->category_id=='7'?1:0,round($settlement->price, 2));
        
        Expense::whereId($request->expense_id)->update(['is_settlement' => 1 ]);
        $redirectUrl = route('settlements.show', [$settlement->id,'tab=detail']);
     
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
    }

    public function edit($id){
        $this->settlement = $settlement = Settlement::findOrFail($id);
        $this->editPermission = user()->permission('edit_settlements');

        // abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->expense->added_by == user()->id)));

        $exp1 = Expense::whereUser_idAndIs_settlement(user()->id,0)->whereIn('status',['paid','done']);
        $exp2 = Expense::whereId($settlement->expense_id);
        $this->expenses = $exp2->union($exp1)->get();
        return view('settlements.ajax.edit', $this->data);
    }

    public function update(Request $request, $id){
        $settlement = Settlement::findOrFail($id);
        Expense::whereId($settlement->expense_id)->update(['is_settlement' => 0 ]);

        $settlement->expense_id = $request->expense_id;
        $settlement->save();

        $this->generateApproval($id,$request->urgency=='normal'?0:0,$request->category_id=='7'?1:0,round($request->price, 2));
        
        Expense::whereId($request->expense_id)->update(['is_settlement' => 1 ]);
        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('settlements.index')]);
    }

    public function destroy($id){
        $this->settlement = Settlement::findOrFail($id);
        $this->deletePermission = user()->permission('delete_settlements');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->settlement->user_id == user()->id)));
        Expense::whereId($this->settlement->expense_id)->update(['is_settlement'=>0]);
        Settlement::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function detailCreate(){
        $this->settlementId = request('id');
        $this->settlement = $settlement = Settlement::findOrFail($this->settlementId);
        $employement = $settlement->expense->is_detail==1?'k':'nk';
        $this->categories = ExpensesCategoryDetail::whereType($employement)->orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('settlements.ajax.createDetail', $this->data);
    }

    public function detailStore(StoreSettlementDetail $request){
        $detail = new SettlementDetail();
        $detail->header_id = $request->settlement_id;
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, 'settlement-invoice');
            $detail->bill = $filename;
        }

        $detail->save();

        $amt = SettlementDetail::whereHeader_id($request->settlement_id)->sum('amount');
        Settlement::whereId($request->settlement_id)->update(['price' => $amt ]);
        $set = Settlement::findorfail($request->settlement_id);
        $this->generateApproval($request->settlement_id,$set->expense->urgency=='normal'?0:0,$set->expense->category_id=='7'?1:0,round($amt, 2));
        
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('settlements.show', [$request->settlement_id,'tab=detail'])]);
    }

    public function detailedit($id){
        $this->detail = SettlementDetail::findOrFail($id);
        $this->categories = ExpensesCategoryDetail::orderBy('id', 'asc')->get();
        $this->redirectUrl = request('redirectUrl');
        return view('settlements.ajax.editDetail', $this->data);
    }

    public function detailupdate(Request $request,$id){
        $detail = SettlementDetail::findOrFail($id);
        $detail->category_id = $request->category_id;
        $detail->remarks = $request->remarks;
        $detail->amount = $request->price;
        $detail->estdate = companyToYmd($request->estdate);
        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, 'settlement-invoice');
            $detail->bill = $filename;
        }
        $detail->save();

        $amt = SettlementDetail::whereHeader_id($detail->header_id)->sum('amount');
        Settlement::whereId($detail->header_id)->update(['price' => $amt ]);
        $set = Settlement::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,$set->expense->urgency=='normal'?0:0,$set->expense->category_id=='7'?1:0,round($amt, 2));
        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('settlements.show', [$detail->header_id,'tab=detail'])]);
    }

    public function detailDelete($id){
        $detail = SettlementDetail::whereId($id)->first();
        SettlementDetail::destroy($id);
        $amt = SettlementDetail::whereHeader_id($detail->header_id)->sum('amount');
        Settlement::whereId($detail->header_id)->update(['price' => $amt ]);
        $set = Settlement::findorfail($detail->header_id);
        $this->generateApproval($detail->header_id,$set->expense->urgency=='normal'?0:0,$set->expense->is_detail=='1'?1:0,round($amt, 2));
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function applyQuickAction(Request $request){
        switch ($request->action_type) {
            case 'delete':
                $this->deleteRecords($request);
                    return Reply::success(__('messages.deleteSuccess'));
            case 'change-status':
                $this->changeBulkStatus($request);
                    return Reply::success(__('messages.updateSuccess'));
            default:
                    return Reply::error(__('messages.selectAction'));
            }
    }

    public function approvalList($id){
        $total = SettlementApproval::whereHeader_id($id)->count();
        $this->approval = SettlementApproval::whereHeader_id($id)->whereNotIn('id',SettlementApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        return view('settlements.approval', $this->data);
    }

    public function checkApproval(Request $request){
        $act = SettlementAct::findorfail($request->actid);
        if(SettlementApproval::whereId($act->approval_id)->value('user_id') == user()->id){
            return Reply::success('Have Access to Respond');
        }else{
            return Reply::error('You Don\'t Have Access to Respond');
        }
    }

    public function response($id,$actid){
        $this->header = Settlement::findorfail($id);
        $this->act = $act = SettlementAct::findorfail($actid);
        return view('settlements.response', $this->data);
    }

    public function responseAction(Request $request){
        $act = SettlementAct::findorfail($request->act_id);
        SettlementApproval::whereId($act->approval_id)->update(['status'=>$act->apv_status,'remarks'=>$request->description,'approval_date'=>date('Y-m-d H:i:s')]);
        Settlement::whereId($request->header_id)->update(['status'=>$act->status,'state_id'=>$act->next_state]);
        if($act->name =='Tax Approve'){
            $settlement = Settlement::findorfail($request->header_id);
            $settlement->procurement = $request->procurement;
            $settlement->subject = $request->subject;
            $settlement->tax_no = $request->taxNo;
            $settlement->type_tax_mount = $request->typetaxAmount;
            $settlement->tax_amount_basic = $request->taxAmountBasic;
            $settlement->tax_amount = $request->taxAmount;
            $settlement->type_tax_income1 = $request->typePph1;
            $settlement->tax_income1_basic = $request->pph1Basic;
            $settlement->tax_income1 = $request->pph1;
            $settlement->type_tax_income2 = $request->typePph2;
            $settlement->tax_income2_basic = $request->pph2basic;
            $settlement->tax_income2 = $request->pph2;
            $settlement->type_tax_vat = $request->typePpn;
            $settlement->tax_vat_basic = $request->ppnBasic;
            $settlement->tax_vat = $request->ppn;
            $settlement->tax_total_basic = $request->taxTotalBasic;
            $settlement->tax_total = $request->taxTotal;
            $settlement->save();
        }
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('settlements.index')]);
    }

    public function copy(Request $request){

    }

    public function download($id){
        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForDownload($id){
        $this->header = Settlement::findorfail($id);
        $this->detail = SettlementDetail::whereHeader_id($id)->get();
        $this->approval = SettlementApproval::whereHeader_id($id)->whereNotIn('id',SettlementApproval::whereHeader_id($id)->latest('id')->take(2)->pluck('id'))->get();
        $this->inword = $this->numerator($this->header->price);

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('settlements.pdf.settlement', $this->data);
        $filename = date('Y-m-d H:i:s');

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    protected function deleteRecords($request){
        abort_403(user()->permission('delete_employees') != 'all');

        foreach (Settlements::whereIn('id', explode(',', $request->row_ids))->get() as $delete) {
            $delete->delete();
        }
    }

    private function generateCode($urgency,$employment){
        $last = Settlement::whereRaw('year(created_at)="'.date('Y').'"')->whereRaw('month(created_at)="'.date('m').'"')->latest('id');
        if($last->count() > 0){
            $data = $last->first();
            return "PJ".$urgency.$employment.date('ymd').str_pad(((int)substr($data->code,10,4) +1), 4, '0', STR_PAD_LEFT);
        }else{
            return "PJ".$urgency.$employment.date('ymd')."0001";
        }
    }

    protected function generateApproval($id,$urgency,$employment,$price){
        $apvid = Approval::whereNameAndCompany_idAndIs_urgencyAndIs_employment('settlement',user()->company_id,$urgency,$employment)
        ->where('limit_min', '<=', $price)
        ->Where(function ($query) use ($price) {
            $query->where('limit_max', '0')
            ->orWhere('limit_max', '>=', $price);
        })->value('id');

        $stmapv = Settlement::whereId($id)->value('approval_id');
        if($apvid <> $stmapv){
            SettlementApproval::Where('header_id',$id)->delete();

            if(SettlementApproval::count()>0){
                $expapv = SettlementApproval::latest('id')->first();
                $expact = SettlementAct::latest('id')->first();
                DB::statement('ALTER TABLE settlement_approval AUTO_INCREMENT = '.$expapv->id.';');
                DB::statement('ALTER TABLE settlement_act AUTO_INCREMENT = '.$expact->id.';');
            }

            Settlement::whereId($id)->update(['approval_id' => $apvid ]);
            $state = ApprovalState::whereApproval_id($apvid)->get();

            foreach($state as $s){
                if(!filter_var($s->users, FILTER_VALIDATE_INT)){
                    $user = $this->getUserApproval($s->users,user()->id);
                }else{
                    $user = $s->users;
                }
                $apv = new SettlementApproval;
                $apv->header_id = $id;
                $apv->state_id = $s->state_id;
                $apv->user_id = $user;
                $apv->save();

                $apvact = ApprovalAct::whereState_idAndApproval_id($s->state_id,$apvid)->get();

                foreach($apvact as $aa){
                    $act = new SettlementAct;
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