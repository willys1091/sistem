@php
    $deleteReimbursementCategoryPermission = user()->permission('manage_reimbursement_category');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.reimbursements.reimbursementCategory')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-table class="table-bordered" headType="thead-light">
        <x-slot name="thead">
            <th>#</th>
            <th>@lang('modules.projectCategory.categoryName')</th>
            <th>@lang('modules.reimbursementCategory.allowRoles')</th>
            <th>@lang('modules.reimbursementCategory.isDetail')</th>
            <th class="text-right">@lang('app.action')</th>
        </x-slot>

        @forelse($categories as $key=>$item)
            <tr id="row-{{ $item->id }}">
                <td>{{ $key + 1 }}</td>
                <td data-row-id="{{ $item->id }}" contenteditable="true">{{ $item->category_name }}</td>
                <td>
                    <div class='form-group mb-0'>
                        <select name="cat_role[]" data-row-id="{{ $item->id }}" id="cat_role-{{ $item->id }}" multiple class="form-control select-picker cat_roles" data-size="8" >
                            @foreach($roles as $role)
                                @php $selected = ''; @endphp

                                @foreach ($item->roles as $roledata)
                                    @if ($roledata->role_id == $role->id)
                                        @php $selected = 'selected'; @endphp
                                    @endif
                                @endforeach
                                <option {{ $selected }} value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td data-row-id="{{ $item->id }}" contenteditable="false">{{ $item->is_detail==0?'Not Detail':'Detail' }}</td>
                <td class="text-right">
                    @if ($deleteReimbursementCategoryPermission == 'all' || ($deleteReimbursementCategoryPermission == 'added' && $item->added_by == user()->id))
                        <x-forms.button-secondary data-row-id="{{ $item->id }}" icon="trash" class="delete-row">
                            @lang('app.delete')</x-forms.button-secondary>
                    @endif
                </td>
            </tr>
        @empty
            <x-cards.no-record-found-list colspan="4" />

        @endforelse
    </x-table>

    <x-form id="createProjectCategory">
        <div class="row border-top-grey ">
            <div class="col-sm-12">
                <x-forms.text fieldId="category_name" :fieldLabel="__('modules.projectCategory.categoryName')" fieldName="category_name"
                    fieldRequired="true" :fieldPlaceholder="__('placeholders.category')">
                </x-forms.text>
            </div>
            <div class="col-sm-12">
                <x-forms.select fieldId="role" :fieldLabel="__('modules.reimbursementCategory.assignToRole')" multiple="true" fieldName="role[]">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-category" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(".select-picker").selectpicker();

    $('.delete-row').click(function() {

        var id = $(this).data('row-id');
        var url = "{{ route('reimbursementCategory.destroy', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

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
                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            $('#row-' + id).fadeOut();
                            $('#reimbursement_category_id').html(response.data);
                            $('#reimbursement_category_id').selectpicker('refresh');
                        }
                    }
                });
            }
        });

    });

    $('#save-category').click(function() {
        var url = "{{ route('reimbursementCategory.store') }}";
        $.easyAjax({
            url: url,
            container: '#createProjectCategory',
            type: "POST",
            data: $('#createProjectCategory').serialize(),
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-category",
            success: function(response) {
                if (response.status == 'success') {
                    if (response.status == 'success') {
                        $('#reimbursement_category_id').html(response.data);
                        $('#reimbursement_category_id').selectpicker('refresh');
                        $(MODAL_LG).modal('hide');
                    }
                }
            }
        })
    });

    $('#save-category').click(function() {
       let userId = $('#user_id').val();

        const url = "{{ route('reimbursements.get_employee_projects') }}";
        let data = $('#save-reimbursement-data-form').serialize();

        if (userId != '') {
            setTimeout(function() {
                $.easyAjax({
                    url: url,
                    type: "GET",
                    data: {'userId' : userId},
                    success: function(response) {
                        $('#reimbursement_category_id').html('<option value="">--</option>'+response.category);
                        $('#reimbursement_category_id').selectpicker('refresh')
                    }
                });
            }, 2000);
        }
    });


    $('[contenteditable=true]').focus(function() {
        $(this).data("initialText", $(this).html());
        let rowId = $(this).data('row-id');
    }).blur(function() {
        // ...if content is different...
        if ($(this).data("initialText") !== $(this).html()) {
            let id = $(this).data('row-id');
            let value = $(this).html();

            var url = "{{ route('reimbursementCategory.update', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                url: url,
                container: '#row-' + id,
                type: "POST",
                data: {
                    'category_name': value,
                    '_token': token,
                    '_method': 'PUT'
                },
                blockUI: true,
                success: function(response) {
                    if (response.status == 'success') {
                        $('#reimbursement_category_id').html(response.data);
                        $('#reimbursement_category_id').selectpicker('refresh');
                    }
                }
            })
        }
    });

    $('.cat_roles').change(function() {
        let id = $(this).data('row-id');

        if (typeof id === 'undefined') {
            return false;
        }

        let value = $(this).val();

        var url = "{{ route('reimbursementCategory.update', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: url,
            container: '#row-' + id,
            type: "POST",
            data: {
                'roles': value,
                '_token': token,
                'role_update': 1,
                '_method': 'PUT'
            },
            blockUI: true,
            success: function(response) {
                $('#reimbursement_category_id').html(response.data);
                $('#reimbursement_category_id').selectpicker('refresh')
            }
        })
    });

    $('.cat_roles').change(function() {
        let categoryId = $('#reimbursement_category_id').val();

        const url = "{{ route('reimbursements.get_category_employees') }}";
        let data = $('#save-reimbursement-data-form').serialize();

        if (categoryId != '') {

            setTimeout(function() {
                $.easyAjax({
                    url: url,
                    type: "GET",
                    data: {'categoryId' : categoryId},
                    success: function(response) {
                        $('#user_id').html('<option value="">--</option>'+response.employees);
                        $('#user_id').selectpicker('refresh')
                        $('#reimbursement_category_id').html('<option value="">--</option>'+response.category);
                        $('#reimbursement_category_id').selectpicker('refresh')
                    }
                });
            }, 2000);
        }
    });

</script>
