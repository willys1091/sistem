<div class="modal-header">
    <h5 class="modal-title">@lang('app.search')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<x-form id="save-expense-data-form">
    <div class="modal-body">
        <div class="portlet-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group my-3">
                        <input type="hidden" id="header_id" name="header_id" value="{{ $header->id}}">
                        <input type="hidden" id="act_id" name="act_id" value="{{ $act->id}}">
                        <x-forms.label fieldId="description" :fieldLabel="__('app.remarks')"></x-forms.label>
                        <div id="description"></div>
                        <textarea name="description" id="description-text" class="d-none"></textarea>
                    </div>
                </div>
            </div>
            @php $act = explode(" ",$act->name); @endphp
            @if($act[0] == 'Tax' && $act[1] == 'Approve')
            <div class="row">
                <div class="col-md-2 col-lg-2 pt-5">
                    <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.isTax')" :checked="1" fieldName="isTax" fieldId="isTax" fieldValue="yes"/>
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="procurement" fieldRequired="true" :fieldLabel="__('modules.reimbursements.procurement')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="procurement" id="procurement" data-live-search="true">
                        <option value="">--</option>
                        <option value="barang">Barang</option>
                        <option value="jasa">Jasa</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="subject" fieldRequired="true" :fieldLabel="__('modules.reimbursements.subject')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="subject" id="subject" data-live-search="true">
                        <option value="">--</option>
                        <option value="orang pribadi">Orang Pribadi</option>
                        <option value="badan">Badan</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3 taxNo">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.taxNo')" fieldName="taxNo" fieldRequired="true" fieldId="taxNo" />
                </div>
                <div class="col-md-2 col-lg-2 pt-5">
                    <x-forms.checkbox class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.isTaxNo')" :checked="1" fieldName="isTaxNo" fieldId="isTaxNo" fieldValue="yes"/>
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="subject" fieldRequired="true" :fieldLabel="__('modules.reimbursements.typetaxAmount')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="typetaxAmount" id="typetaxAmount" data-live-search="true">
                        <option value="">--</option>
                        <option value="normal">Normal</option>
                        <option value="gross up">Gross Up</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.amountBasic')" fieldName="taxAmountBasic" fieldRequired="true" fieldId="taxAmountBasic" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.amount')" fieldName="taxAmount" fieldRequired="true" fieldId="taxAmount" :fieldPlaceholder="__('placeholders.price')" />
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="typePph1" fieldRequired="true" :fieldLabel="__('modules.reimbursements.typePph1')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="typePph1" id="typePph1" data-live-search="true">
                        <option value="">--</option>
                        <option value="15">15</option>
                        <option value="21">21</option>
                        <option value="23">23</option>
                        <option value="4(2)">4(2)</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.pph1Basic')" fieldName="pph1Basic" fieldRequired="true" fieldId="pph1Basic" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.pph1')" fieldName="pph1" fieldRequired="true" fieldId="pph1" :fieldPlaceholder="__('placeholders.price')" />
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="typePph2" fieldRequired="true" :fieldLabel="__('modules.reimbursements.typePph2')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="typePph2" id="typePph2" data-live-search="true">
                        <option value="">--</option>
                        <option value="15">15</option>
                        <option value="21">21</option>
                        <option value="23">23</option>
                        <option value="4(2)">4(2)</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.pph2Basic')" fieldName="pph2basic" fieldRequired="true" fieldId="pph2basic" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.pph2')" fieldName="pph2" fieldRequired="true" fieldId="pph2" :fieldPlaceholder="__('placeholders.price')" />
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.label class="mt-3" fieldId="typePpn" fieldRequired="true" :fieldLabel="__('modules.reimbursements.typePpn')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="typePpn" id="typePpn" data-live-search="true">
                        <option value="">--</option>
                        <option value="ppn">PPN</option>
                        <option value="nonppn">Non PPN</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.ppnBasic')" fieldName="ppnBasic" fieldRequired="true" fieldId="ppnBasic" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.ppn')" fieldName="ppn" fieldRequired="true" fieldId="ppn" :fieldPlaceholder="__('placeholders.price')" />
                </div>
            </div>
            <div class="row isTax">
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.taxTotalBasic')" fieldName="taxTotalBasic" fieldRequired="true" fieldId="taxTotalBasic" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-sm-12 col-lg-3">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.reimbursements.taxTotal')" fieldName="taxTotal" fieldRequired="true" fieldId="taxTotal" :fieldPlaceholder="__('placeholders.price')" />
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-primary id="save-expense-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    </div>
</x-form>
<script>
    $(document).ready(function() {
        quillMention(null, '#description');
        $('#isTax').change(function() {
            if($(this).is(':checked')){
                $('.isTax').show();
            }else{
                $('.isTax').hide();
            }
        });

        $('#isTaxNo').change(function() {
            if($(this).is(':checked')){
                $('.taxNo').show();
            }else{
                $('.taxNo').hide();
            }
        });

        $('#save-expense-form').click(function() {
            let note = document.getElementById('description').children[0].innerHTML;
            document.getElementById('description-text').value = note;
            var mention_user_id = $('#description span[data-id]').map(function(){ return $(this).attr('data-id') }).get();
            $('#mentionUserId').val(mention_user_id.join(','));
            const url = "{{ route('pettycashes.response_action') }}";
            var data = $('#save-expense-data-form').serialize();

            $.easyAjax({
                url: url,
                container: '#save-expense-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-expense-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });
    });
</script>