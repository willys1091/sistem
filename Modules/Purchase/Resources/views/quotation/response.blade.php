<div class="modal-header">
    <h5 class="modal-title">@lang('app.search')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<x-form id="save-purchaserequest-data-form">
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
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-primary id="save-purchaserequest-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
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

        $('#save-purchaserequest-form').click(function() {
            let note = document.getElementById('description').children[0].innerHTML;
            document.getElementById('description-text').value = note;
            var mention_user_id = $('#description span[data-id]').map(function(){ return $(this).attr('data-id') }).get();
            $('#mentionUserId').val(mention_user_id.join(','));
            const url = "{{ route('purchase_request.response_action') }}";
            var data = $('#save-purchaserequest-data-form').serialize();

            $.easyAjax({
                url: url,
                container: '#save-purchaserequest-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-purchaserequest-form",
                data: data,
                file: true,
                success: function(response) {
                    window.location.href = response.redirectUrl;
                }
            });
        });
    });
</script>