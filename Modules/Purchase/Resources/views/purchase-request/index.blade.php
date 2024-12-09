@extends('layouts.app')
@push('datatable-styles') @include('sections.datatable_css') @endpush
@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey" id="datatableRange" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.status')</p>
            <div class="select-status">
                <select class="form-control select-picker" id="filter-status">
                    <option value="all">@lang('app.all')</option>
                    <option value="draft">@lang('app.draft')</option>
                    <option value="on progress">@lang('app.onProgress')</option>
                    <option value="approved">@lang('app.approved')</option>
                    <option value="rejected">@lang('app.rejected')</option>
                </select>
            </div>
        </div>
        <div class="task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey"><i class="fa fa-search f-13 text-dark-grey"></i></span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                        placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">@lang('app.clearFilters')</x-forms.button-secondary>
        </div>
    </x-filters.filter-box>
@endsection

@php $addBankAccountPermission = user()->permission('add_bankaccount');@endphp

@section('content')

    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center mt-3">
                {{-- <x-forms.link-primary :link="route('purchase-request.create')" class="mr-3 float-left openRightModal" icon="plus">@lang('app.add') @lang('app.request')</x-forms.link-primary> --}}
                <x-forms.button-primary class="mr-3 float-left" id="add-header" icon="plus" data-redirect-url="{{ route('purchase-request.index') }}">
                    @lang('app.add') @lang('app.request')
                </x-forms.button-primary>
            </div>
        </div>
        
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">{!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}</div>
    </div>
@endsection
@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('body').on('click', '#add-header', function() {
            let redirectUrl = encodeURIComponent($(this).data("redirect-url"));
            var url = "{{ route('purchase-request.create') }}?redirectUrl="+redirectUrl;

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.open-edit', function() {
            var id = $(this).data('request-id');
            var url = "{{ route('purchase-request.edit', ':id') }}";
            url = url.replace(':id', id);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#purchase-request-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = $('#datatableRange').val();
            let endDate;
            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }
            
            const searchText = $('#search-text-field').val();
            const date_filter_on = $('#date_filter_on').val();

            var status = $('#filter-status').val();

            data['searchText'] = searchText;
            data['status'] = status;
            data['date_filter_on'] = date_filter_on;
            data['startDate'] = startDate;
            data['endDate'] = endDate;
        });
        const showTable = () => {
            window.LaravelDataTables["purchase-request-table"].draw();
        }

        $('#search-text-field, #date_filter_on, #filter-status')
            .on('change keyup',
                function() {
                    if ($('#filter-status').val() != "all") {
                        $('#reset-filters').removeClass('d-none');
                        showTable();
                    } else if ($('#search-text-field').val() != "") {
                        $('#reset-filters').removeClass('d-none');
                    }else if ($('#date_filter_on').val() != "start_date") {
                        $('#reset-filters').removeClass('d-none');
                    }else {
                        $('#reset-filters').addClass('d-none');
                    }
                    showTable();
                });


        $('body').on('click', '#reset-filters', function () {
            $('#filter-form')[0].reset();
            $('.filter-box #date_filter_on').val('start_date');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '#reset-filters-2', function () {
            $('#filter-form')[0].reset();
            $('.filter-box #date_filter_on').val('start_date');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
            } else {
                $('#quick-action-apply').attr('disabled', true);
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
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
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        const applyQuickAction = () => {
            var rowdIds = $("#purchase-request-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('bankaccounts.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                    }
                }
            })
        };

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('request-id');
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
                    var url = "{{ route('purchase-request.destroy', ':id') }}";
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

        $('body').on('click', '.sendButton', function() {
            var id = $(this).data('request-id');
            var dataType = $(this).data('type');
            var url = "{{ route('purchase_request.send_request', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                type: 'POST',
                url: url,
                container: '#purchase-request-table',
                blockUI: true,
                data: {
                    '_token': token,
                    'data_type' : dataType
                },
                success: function(response) {
                    if (response.status == "success") {
                        showTable();
                    }
                }
            });
        });

        $('body').on('change', '.change-account-status', function() {
            var id = $(this).data('account-id');
            var url = "{{ route('bankaccounts.change_status') }}";

            var token = "{{ csrf_token() }}";
            var status = $(this).val();

            if (typeof id !== 'undefined') {
                $.easyAjax({
                    url: url,
                    type: "POST",
                    data: {
                        '_token': token,
                        accountId: id,
                        status: status
                    },

                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                            resetActionButtons();
                            deSelectAll();
                        }
                    }
                });
            }
        });

        $('body').on('click', '.open-approval', function() {
            var id = $(this).data('purchaserequest-id');
            var url = "{{ route('purchase_request.approval_list', ':id') }}";
            url = url.replace(':id', id);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        $('body').on('click', '.open-response', function() {
            var id = $(this).data('purchaserequest-id');
            var actid = $(this).data('act-id');
            $.ajax({
                type: "POST",
                url: "{{ route('purchase_request.check_approval') }}",
                data: {'_token': "{{ csrf_token() }}",actid: actid},
                cache: false,
                success: function(response){
                    if (response.status == "success") {
                        var url = "{{ route('purchase_request.response', [':id', ':actid']) }}";
                        url = url.replace(':id', id).replace(':actid', actid);
                        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                        $.ajaxModal(MODAL_XL, url);
                    }else{
                        Swal.fire({
                        icon: 'error',
                        text: response.message,

                        toast: true,
                        position: "top-end",
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,

                        customClass: {
                            confirmButton: "btn btn-primary",
                        },
                        showClass: {
                            popup: "swal2-noanimation",
                            backdrop: "swal2-noanimation",
                        },
                    });
                    }
                }
            });
        });

        $('body').on('click', '.copy', function() {
            var id = $(this).data('purchaserequest-id');
            $.easyAjax({
                url: "{{ route('expenses.copy') }}",
                type: "POST",
                data: {'_token': "{{ csrf_token() }}",purchaserequestId: id},
                success: function(response) {
                    if (response.status == "success") {
                        showTable();
                    }
                }
            });
        });

        $('body').on('change', '#delivery-status', function() {
            let id = $(this).data('request-id');
            let value = $(this).val();
            let url = "{{route('purchase_request.change_status', ':id')}}";
            url = url.replace(':id', id);

            $.easyAjax({
                type:"GET",
                url:url,
                data: {delivery_status: value},
                success: function(response) {
                    showTable();
                    resetActionButtons();
                    deSelectAll();
                }
            })
        })
    </script>
@endpush