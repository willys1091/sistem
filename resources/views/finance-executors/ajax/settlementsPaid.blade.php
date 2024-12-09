<x-form id="save-settlement-data-form">
    <div class="modal-header">
        <h5 class="modal-title">Settlements Paid</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <input type="hidden" name="settlement_id" value="{{ $settlementId }}">
            <div class="row">
                <div class="col-md-6">
                    <x-forms.datepicker fieldId="fin_date" fieldRequired="true"
                        :fieldLabel="__('modules.settlements.transferDate')" fieldName="transferDate"
                        :fieldPlaceholder="__('placeholders.date')"
                        :fieldValue="\Carbon\Carbon::today()->format(company()->date_format)" />
                </div>
                <div class="col-lg-12">
                    <x-forms.file :fieldLabel="__('app.attachment')" fieldName="bill" fieldRequired="true" fieldId="bill" allowedFileExtensions="pdf png jpg jpeg" :popover="__('messages.fileFormat.multipleImageFile')" required/>
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

        const dp1 = datepicker('#fin_date', {
            position: 'bl',
            ...datepickerConfig
        });

        $('#save-settlement-form').click(function() {
            const url = "{{ route('financeExecutors.settlements.actionPaid') }}";
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