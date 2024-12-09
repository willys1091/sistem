<x-form id="save-settlement-data-form">@method('PUT')
    <div class="modal-header">
        <h5 class="modal-title">Edit Settlement</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <div class="row">
                <div class="col-md-12">
                    <x-forms.label class="mt-3" fieldId="expense_id" fieldRequired="true" :fieldLabel="__('modules.settlements.expense')"></x-forms.label>
                    <select class="form-control height-35 select-picker" name="expense_id" id="expense_id" data-live-search="true">
                        <option value="">--</option>
                        @foreach ($expenses as $e)
                            <option @selected($e->id == $settlement->expense_id)  value="{{ $e->id }}">{{ $e->code }} - {{ $e->item_name }}</option>
                        @endforeach
                    </select>
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
        $('#save-settlement-form').click(function() {
            const url = "{{ route('settlements.update', $settlement->id) }}";
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
    });
</script>