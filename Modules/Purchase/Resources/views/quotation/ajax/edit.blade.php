<x-form id="save-request-data-form"> @method('PUT')
    <div class="modal-header">
        <h5 class="modal-title">Edit Purchase Request</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-lg-6 pl-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="expected_date" :fieldLabel="__('purchase::modules.quotation.expectedDate')" fieldRequired="true"></x-forms.label>
                <input type="text" id="expected_date" name="expected_date" class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" placeholder="@lang('placeholders.date')" value="{{ $request->request_date->translatedFormat(company()->date_format) }}">
            </div>
            
          
            <div class="col-md-12 col-sm-12 c-inv-note-terms p-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="" class="text-capitalize" :fieldLabel="__('modules.invoices.note')"></x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4" placeholder="@lang('placeholders.invoices.note')">{{ $request->note }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-request-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script src="{{ asset('vendor/jquery/dropzone.min.js') }}"></script>
<script>
    $(document).ready(function() {

        const dp1 = datepicker('#request_date', {
            minDate: new Date(),
            position: 'bl',
            ...datepickerConfig
        });

        const dp2 = datepicker('#estimation_date', {
            minDate: new Date(),
            position: 'bl',
            ...datepickerConfig
        });

        $('#save-request-form').click(function() {
            const url = "{{ route('purchase-request.update', $request->id) }}";
            var data = $('#save-request-data-form').serialize();
            $.easyAjax({
                url: url,
                container: '#save-request-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-request-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });
    });

</script>
