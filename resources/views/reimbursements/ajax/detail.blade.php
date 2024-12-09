<div class="row py-0 py-md-0 py-lg-3">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        @if($reimbursement->status=='draft')
            <div class="d-flex justify-content-between action-bar mt-2">
                <div id="table-actions" class="d-flex align-items-center">
                    <x-forms.button-primary class="mr-3 float-left" id="add-detail" icon="plus" data-redirect-url="{{ route('settlements.show', $reimbursement->id) . '?tab=detail' }}">
                        @lang('app.newDetail')
                    </x-forms.button-primary>
                </div>
            </div>
        @endif
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
</div>
@include('sections.datatable_js')
<script>
    $('#projects-table').on('preXhr.dt', function(e, settings, data) {
        var reimbursement_id = "{{ $reimbursement->id }}";
        data['reimbursement_id'] = reimbursement_id;
    });
    const showTable = () => {
        window.LaravelDataTables["reimbursementsdetail-table"].draw(false);
    }

    $('body').on('click', '#add-detail', function() {
        let redirectUrl = encodeURIComponent($(this).data("redirect-url"));
        var url = "{{ route('reimbursements.detail.create') }}?id="+"{{ $reimbursement->id }}&redirectUrl="+redirectUrl;

        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.open-edit', function() {
        var id = $(this).data('reimbursement-id');
        var url = "{{ route('reimbursements.detail.edit', ':id') }}";
        url = url.replace(':id', id);
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.delete-table-row', function() {
        var id = $(this).data('reimbursement-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('reimbursements.detail.destroy', ':id') }}";
                url = url.replace(':id', id);
                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                        }
                    }
                });
            }
        });
    });
</script>
