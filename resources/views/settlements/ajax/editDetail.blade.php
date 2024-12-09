<x-form id="save-settlement-data-form">
    <div class="modal-header">
        <h5 class="modal-title">Edit Detail</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <div class="row">
                <div class="col-md-12">
                    <x-forms.text :fieldLabel="__('modules.settlements.remarks')" fieldName="remarks" fieldRequired="true" fieldId="remarks" :fieldValue="$detail->remarks" autocomplete="off"/>
                </div>
                <div class="col-md-12">
                    <x-forms.label class="mt-3" fieldId="category_id" :fieldLabel="__('modules.settlements.settlementCategory')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="category_id" id="settlement_category_id" data-live-search="true">
                        <option value="">--</option>
                        @foreach ($categories as $category)
                            <option @selected($category->id == $detail->category_id) value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.amount')" fieldName="price" fieldRequired="true" fieldId="price" :fieldValue="$detail->amount" :fieldPlaceholder="__('placeholders.price')" />
                </div>
                <div class="col-md-6">
                    <x-forms.datepicker fieldId="purchase_date" fieldRequired="true" :fieldLabel="__('modules.settlements.purchaseDate')" fieldName="estdate" :fieldPlaceholder="__('placeholders.date')" :fieldValue="$detail->estdate->format(company()->date_format)" />
                </div>
                <div class="col-lg-12">
                    <x-forms.file :fieldLabel="__('app.attachment')" fieldName="bill" fieldId="bill" allowedFileExtensions="pdf png jpg jpeg" :popover="__('messages.fileFormat.multipleImageFile')" :fieldValue="asset_url_local_s3('settlement-invoice/' . $detail->bill)" required/>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12">
                    @if (!is_null($detail->bill))
                        <x-file-card :fileName="$detail->bill" :dateAdded="$detail->created_at->diffForHumans()">
                            <i class="fa fa-file text-lightest"></i>
                            <x-slot name="action">
                                <div class="dropdown ml-auto file-action">
                                    <button class="btn btn-lg f-14 p-0 text-lightest text-capitalize rounded  dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0" aria-labelledby="dropdownMenuLink" tabindex="0">
                                        <a class="cursor-pointer d-block text-dark-grey f-13 py-3 px-3 " target="_blank" href="{{ asset_url_local_s3('settlement-invoice/' . $detail->bill) }}">@lang('app.view')</a>
                                    </div>
                                </div>
                            </x-slot>
                        </x-file-card>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-settlement-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
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

        const dp1 = datepicker('#purchase_date', {
            position: 'bl',
            ...datepickerConfig
        });

        $('#save-settlement-form').click(function() {
            const url = "{{ route('settlements.detail.update', $detail->id) }}";
            var data = $('#save-settlement-data-form').serialize();
            $.easyAjax({
                url: url,
                container: '#save-settlement-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-settlement-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });

        var file = $('.dropify').dropify({
            messages: dropifyMessages
        });
        
    });
</script>