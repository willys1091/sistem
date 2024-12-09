<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('app.menu.pettycashes') . ' ' . __('app.details')" class=" mt-4">
            @if (is_null($pettycash->pettycashes_recurring_id))
                {{-- <x-slot name="action">
                    <div class="dropdown">
                        <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                            aria-labelledby="dropdownMenuLink" tabindex="0">
                            @php
                                $trashBtn = (!is_null($pettycash->project) && is_null($pettycash->project->deleted_at)) ? true : (is_null($pettycash->project) ? true : false) ;
                            @endphp

                            @if ($trashBtn && $editPettycashPermission == 'all' || ($editPettycashPermission == 'added' && user()->id == $pettycash->added_by))
                                <a class="dropdown-item openRightModal" href="{{ route('pettycashes.edit', [$pettycash->id]) }}">@lang('app.edit')</a>
                            @endif
                            @if ($deletePettycashPermission == 'all' || ($deletePettycashPermission == 'added' && user()->id == $pettycash->added_by))
                                <a class="dropdown-item delete-table-row" href="javascript:;" data-pettycash-id="{{ $pettycash->id }}">@lang('app.delete')</a>
                            @endif
                        </div>
                    </div>
                </x-slot> --}}
            @endif
            <x-cards.data-row :label="__('modules.pettycashes.itemName')" :value="$pettycash->item_name" />

            <x-cards.data-row :label="__('modules.pettycashes.pettycashCategory')" :value="$pettycash->category->category_name ?? '--'" />

            {{-- <x-cards.data-row :label="__('app.urgency')" :value="ucwords($pettycash->urgency) ?? '--'" /> --}}

            <x-cards.data-row :label="__('app.paymentType')" :value="ucwords($pettycash->payment_type) ?? '--'" />
            
            @if($pettycash->payment_type == 'transfer')
                <x-cards.data-row :label="__('modules.pettycashes.payee')" :value="ucwords($pettycash->payee) ?? '--'" />
                <x-cards.data-row :label="__('app.menu.bankaccount')" :value="ucwords($pettycash->bank_account) ?? '--'" />
                <x-cards.data-row :label="__('modules.pettycashes.bankName')" :value="ucwords($pettycash->bank_name) ?? '--'" />
            @endif

            <x-cards.data-row :label="__('app.price')" :value="$pettycash->total_amount" />

            <x-cards.data-row :label="__('modules.pettycashes.purchaseDate')" :value="(!is_null($pettycash->purchase_date) ? $pettycash->purchase_date->translatedFormat(company()->date_format) : '--')" />

            <x-cards.data-row :label="__('modules.pettycashes.purchaseFrom')" :value="$pettycash->purchase_from ?? '--'" />

            <x-cards.data-row :label="__('app.client')" :value="(!is_null($pettycash->client) ? $pettycash->client->company_name : '--')" />

            <x-cards.data-row :label="__('app.project')" :value="(!is_null($pettycash->project) && !is_null($pettycash->project->withTrashed()) ? $pettycash->project->project_name : '--')" />

            @php
                $bankName = !is_null($pettycash->bankAccount) ? ($pettycash->bankAccount->bank_name . ' | ' . $pettycash->bankAccount->account_name ?? '') : '--';
            @endphp
            <x-cards.data-row :label="__('app.menu.bankaccount')" :value="$bankName !== '' ? $bankName : '--'" />

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('app.bill')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if (!is_null($pettycash->bill))
                        <a target="_blank" href="{{ $pettycash->bill_url }}" class="text-darkest-grey">@lang('app.view') <i class="fa fa-link"></i></a>&nbsp
                        <a href="{{ $pettycash->bill_url }}" class="text-darkest-grey" download>@lang('app.download')<i class="fa fa-download f-w-500 mr-1 f-11"></i></a>
                    @else
                        --
                    @endif
                </p>
            </div>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize"> @lang('app.employee')</p>
                <p class="mb-0 text-dark-grey f-14"><x-employee :user="$pettycash->user" /></p>
            </div>
            <x-cards.data-row :label="__('app.description')" :value="!empty($pettycash->description) ? $pettycash->description : '--'"html="true"/>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">
                    @lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if ($pettycash->status == 'draft')
                        <x-status :value="ucfirst($pettycash->status)" color="grey" />
                    @elseif ($pettycash->status == 'active')
                        <x-status :value="ucfirst($pettycash->status)" color="light-blue" />
                    @elseif ($pettycash->status == 'on progress')
                        <x-status :value="ucfirst($pettycash->status)" color="yellow" />
                    @elseif ($pettycash->status == 'approved')
                        <x-status :value="ucfirst($pettycash->status)" color="light-green" />
                    @else
                        <x-status :value="ucfirst($pettycash->status)" color="red" />
                    @endif
                </p>
            </div>

            @if ($pettycash->status == 'approved')
                <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                    <p class="mb-0 text-lightest f-14 w-30 text-capitalize"> @lang('modules.pettycashes.approvedBy')</p>
                    <p class="mb-0 text-dark-grey f-14"><x-employee :user="$pettycash->approver" /></p>
                </div>
            @endif
            <x-forms.custom-field-show :fields="$fields" :model="$pettycash"></x-forms.custom-field-show>
        </x-cards.data>
    </div>
</div>