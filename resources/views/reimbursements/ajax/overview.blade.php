<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('app.menu.reimbursements') . ' ' . __('app.details')" class=" mt-4">
            @if (is_null($reimbursement->reimbursements_recurring_id))
                {{-- <x-slot name="action">
                    <div class="dropdown">
                        <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                            aria-labelledby="dropdownMenuLink" tabindex="0">
                            @php
                                $trashBtn = (!is_null($reimbursement->project) && is_null($reimbursement->project->deleted_at)) ? true : (is_null($reimbursement->project) ? true : false) ;
                            @endphp

                            @if ($trashBtn && $editReimbursementPermission == 'all' || ($editReimbursementPermission == 'added' && user()->id == $reimbursement->added_by))
                                <a class="dropdown-item openRightModal" href="{{ route('reimbursements.edit', [$reimbursement->id]) }}">@lang('app.edit')</a>
                            @endif
                            @if ($deleteReimbursementPermission == 'all' || ($deleteReimbursementPermission == 'added' && user()->id == $reimbursement->added_by))
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-reimbursement-id="{{ $reimbursement->id }}">@lang('app.delete')</a>
                            @endif
                        </div>
                    </div>
                </x-slot> --}}
            @endif
            <x-cards.data-row :label="__('modules.reimbursements.itemName')" :value="$reimbursement->item_name" />

            <x-cards.data-row :label="__('modules.reimbursements.reimbursementCategory')" :value="$reimbursement->category->category_name ?? '--'" />

            <x-cards.data-row :label="__('app.urgency')" :value="ucwords($reimbursement->urgency) ?? '--'" />

            <x-cards.data-row :label="__('app.paymentType')" :value="ucwords($reimbursement->payment_type) ?? '--'" />
            
            @if($reimbursement->payment_type == 'transfer')
                <x-cards.data-row :label="__('modules.reimbursements.payee')" :value="ucwords($reimbursement->payee) ?? '--'" />
                <x-cards.data-row :label="__('app.menu.bankaccount')" :value="ucwords($reimbursement->bank_account) ?? '--'" />
                <x-cards.data-row :label="__('modules.reimbursements.bankName')" :value="ucwords($reimbursement->bank_name) ?? '--'" />
            @endif

            <x-cards.data-row :label="__('app.price')" :value="$reimbursement->total_amount" />

            <x-cards.data-row :label="__('modules.reimbursements.purchaseDate')" :value="(!is_null($reimbursement->purchase_date) ? $reimbursement->purchase_date->translatedFormat(company()->date_format) : '--')" />

            <x-cards.data-row :label="__('modules.reimbursements.purchaseFrom')" :value="$reimbursement->purchase_from ?? '--'" />

            <x-cards.data-row :label="__('app.client')" :value="(!is_null($reimbursement->client) ? $reimbursement->client->company_name : '--')" />

            <x-cards.data-row :label="__('app.project')" :value="(!is_null($reimbursement->project) && !is_null($reimbursement->project->withTrashed()) ? $reimbursement->project->project_name : '--')" />

            @php
                $bankName = !is_null($reimbursement->bankAccount) ? ($reimbursement->bankAccount->bank_name . ' | ' . $reimbursement->bankAccount->account_name ?? '') : '--';
            @endphp
            <x-cards.data-row :label="__('app.menu.bankaccount')" :value="$bankName !== '' ? $bankName : '--'" />

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('app.bill')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if (!is_null($reimbursement->bill))
                        <a target="_blank" href="{{ $reimbursement->bill_url }}" class="text-darkest-grey">@lang('app.view') <i class="fa fa-link"></i></a>&nbsp
                        <a href="{{ $reimbursement->bill_url }}" class="text-darkest-grey" download>@lang('app.download')<i class="fa fa-download f-w-500 mr-1 f-11"></i></a>
                    @else
                        --
                    @endif
                </p>
            </div>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize"> @lang('app.employee')</p>
                <p class="mb-0 text-dark-grey f-14"><x-employee :user="$reimbursement->user" /></p>
            </div>
            <x-cards.data-row :label="__('app.description')" :value="!empty($reimbursement->description) ? $reimbursement->description : '--'"html="true"/>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if ($reimbursement->status == 'draft')
                        <x-status :value="ucfirst($reimbursement->status)" color="grey" />
                    @elseif ($reimbursement->status == 'active')
                        <x-status :value="ucfirst($reimbursement->status)" color="light-blue" />
                    @elseif ($reimbursement->status == 'on progress')
                        <x-status :value="ucfirst($reimbursement->status)" color="yellow" />
                    @elseif ($reimbursement->status == 'approved')
                        <x-status :value="ucfirst($reimbursement->status)" color="light-green" />
                    @else
                        <x-status :value="ucfirst($reimbursement->status)" color="red" />
                    @endif
                </p>
            </div>

            @if ($reimbursement->status == 'approved')
                <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                    <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('modules.reimbursements.approvedBy')</p>
                    <p class="mb-0 text-dark-grey f-14"><x-employee :user="$reimbursement->approver" /></p>
                </div>
            @endif
            <x-forms.custom-field-show :fields="$fields" :model="$reimbursement"></x-forms.custom-field-show>
        </x-cards.data>
    </div>
</div>