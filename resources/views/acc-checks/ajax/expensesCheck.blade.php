<x-form id="save-expense-data-form">
    <div class="modal-header">
        <h5 class="modal-title">Expenses Accounting Check</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <input type="hidden" name="expense_id" value="{{ $expenseId }}">
            <div class="row">
                <div class="col-md-6">
                    <x-forms.text :fieldLabel="__('modules.expenses.accNo')" fieldName="accNo" fieldId="accNo" fieldRequired="true"/>
                </div>
                <div class="col-md-6">
                    <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp" class="mr-0 mr-lg-2 mr-md-2 cropper" :fieldLabel="__('app.attachmentFile')" fieldName="bill" fieldId="image" fieldHeight="119" :popover="__('messages.fileFormat.ImageFile')" />
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-expense-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>
<script>
    $(document).ready(function() {
        $('#save-expense-form').click(function() {
            const url = "{{ route('accChecks.expenses.actionCheck') }}";
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