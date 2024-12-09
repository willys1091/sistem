<x-form id="save-purchase-request-data-form">
    <div class="modal-header">
        <h5 class="modal-title">Edit Detail</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <div class="row">
                <div class="col-md-12">
                    <x-forms.label class="mt-3" fieldRequired="true" fieldId="product_id" :fieldLabel="__('purchase::modules.purchaseRequest.product')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="product_id" id="product_id" data-live-search="true">
                        <option value="">--</option>
                        @foreach ($product as $p)
                            <option value="{{ $p->id }}" @selected($p->id == $detail->product_id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('purchase::app.qty')" fieldName="qty" fieldRequired="true" fieldId="qty" :fieldValue="$detail->quantity"/>
                </div>
                <div class="col-md-6 uom">
                    <x-forms.label class="mt-3" fieldRequired="true" fieldId="unit_id" :fieldLabel="__('purchase::modules.purchaseRequest.uom')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="unit_id" id="unit_id" data-live-search="true">
                        <option value="">--</option>
                        <option value="{{ $unitType->unit_type }}" {{ $unitType->unit_type == $detail->uom?'selected':'' }}>{{ $unitType->unit_type }}</option>
                        {!! (($unitType->unit_type2==$unitType->unit_type)?'':'<option value="'.$unitType->unit_type2.'" '.(($unitType->unit_type2 == $detail->uom)?'selected':'').'>'.$unitType->unit_type2.'</option>') !!}
                        {!! (($unitType->unit_type3==$unitType->unit_type)||($unitType->unit_type3==$unitType->unit_type2)?'':'<option value="'.$unitType->unit_type3.'" '.(($unitType->unit_type3 == $detail->uom)?'selected':'').'>'.$unitType->unit_type3.'</option>') !!}
                    </select>
                </div>
                <div class="col-md-12">
                    <x-forms.text :fieldLabel="__('purchase::modules.purchaseRequest.remarks')" fieldName="remarks" fieldRequired="true" fieldId="remarks" autocomplete="off" :fieldValue="$detail->item_summary"/>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-purchase-request-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>
<script>
    $(document).ready(function() {
        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        $('#product_id').change(function() {
            let product_id = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{ route('purchase_request.get_uom') }}",
                data: {'_token': "{{ csrf_token() }}",product_id: product_id},
                cache: false,
                success: function(response){
                    $('.uom').html(response);
                }
            });
        });

        $('#save-purchase-request-form').click(function() {
           const url = "{{ route('purchase_request.detail.update', $detail->id) }}";
            var data = $('#save-purchase-request-data-form').serialize();
            $.easyAjax({
                url: url,
                container: '#save-purchase-request-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-purchase-request-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });
    });
</script>