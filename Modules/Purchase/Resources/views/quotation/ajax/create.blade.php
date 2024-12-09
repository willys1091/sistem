<x-form id="save-quotation-data-form">
    <div class="modal-header">
        <h5 class="modal-title">New Quotation</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-lg-6 pl-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="expected_date" :fieldLabel="__('purchase::modules.quotation.expectedDate')" fieldRequired="true"></x-forms.label>
                <input type="text" id="expected_date" name="expected_date" class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" placeholder="@lang('placeholders.date')" value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
            </div>
            <div class="col-md-6 col-lg-6 pl-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="expense_id" fieldRequired="true" :fieldLabel="__('modules.settlements.expense')"></x-forms.label>
                <select class="form-control height-35 select-picker" name="expense_id" id="expense_id" data-live-search="true">
                    <option value="">--</option>
                    @foreach ($request as $r)
                        <option value="{{ $r->id }}">{{ $r->code }} - {{ $r->note }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-quotation-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>
<script>
    $(document).ready(function() {
        const dp1 = datepicker('#expected_date', {
            minDate: new Date(),
            position: 'bl',
            ...datepickerConfig
        });

        $('#save-quotation-form').click(function() {
            const url = "{{ route('quotation.store') }}";
            var data = $('#save-quotation-data-form').serialize();
            $.easyAjax({
                url: url,
                container: '#save-quotation-data-form',
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