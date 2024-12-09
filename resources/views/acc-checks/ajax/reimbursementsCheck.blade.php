<x-form id="save-reimbursement-data-form">
    <div class="modal-header">
        <h5 class="modal-title">Reimbursement Accunting Check</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    </div>
    <div class="modal-body">
        <div class="portlet-body">
            <input type="hidden" name="reimbursement_id" value="{{ $reimbursementId }}">
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
        <x-forms.button-primary id="save-reimbursement-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>
<script>
    $(document).ready(function() {
        $('#save-reimbursement-form').click(function() {
            const url = "{{ route('accChecks.reimbursements.actionCheck') }}";
            var data = $('#save-reimbursement-data-form').serialize();
            $.easyAjax({
                url: url,
                container: '#save-reimbursement-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-reimbursement-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });
    });
</script>