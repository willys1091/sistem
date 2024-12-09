<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('app.menu.settlements') . ' ' . __('app.details')" class=" mt-4">
            <x-cards.data-row :label="__('modules.settlements.itemName')" :value="$settlement->expense->item_name" />
            <x-cards.data-row :label="__('modules.settlements.settlementCategory')" :value="$settlement->expense->category->category_name ?? '--'" />
            <x-cards.data-row :label="__('app.urgency')" :value="ucwords($settlement->expense->urgency) ?? '--'" />
            <x-cards.data-row :label="__('app.paymentType')" :value="ucwords($settlement->expense->payment_type) ?? '--'" />
            @if($settlement->payment_type == 'transfer')
                <x-cards.data-row :label="__('modules.settlements.payee')" :value="ucwords($settlement->expense->payee) ?? '--'" />
                <x-cards.data-row :label="__('app.menu.bankaccount')" :value="ucwords($settlement->expense->bank_account) ?? '--'" />
                <x-cards.data-row :label="__('modules.settlements.bankName')" :value="ucwords($settlement->expense->bank_name) ?? '--'" />
            @endif
            <x-cards.data-row :label="__('app.price')" :value="$settlement->price" />

            <x-cards.data-row :label="__('app.client')" :value="(!is_null($settlement->expense->client) ? $settlement->expense->client->company_name : '--')" />

            <x-cards.data-row :label="__('app.project')" :value="(!is_null($settlement->expense->project) && !is_null($settlement->expense->project->withTrashed()) ? $settlement->project->project_name : '--')" />

            @php
                $bankName = !is_null($settlement->expense->bankAccount) ? ($settlement->expense->bankAccount->bank_name . ' | ' . $settlement->expense->bankAccount->account_name ?? '') : '--';
            @endphp
            <x-cards.data-row :label="__('app.menu.bankaccount')" :value="$bankName !== '' ? $bankName : '--'" />

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('app.bill')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if (!is_null($settlement->expense->bill))
                        <a target="_blank" href="{{ $settlement->expense->bill_url }}" class="text-darkest-grey">@lang('app.view') <i class="fa fa-link"></i></a>&nbsp
                        <a href="{{ $settlement->expense->bill_url }}" class="text-darkest-grey" download>@lang('app.download')<i class="fa fa-download f-w-500 mr-1 f-11"></i></a>
                    @else
                        --
                    @endif
                </p>
            </div>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize"> @lang('app.employee')</p>
                <p class="mb-0 text-dark-grey f-14"><x-employee :user="$settlement->user" /></p>
            </div>
            <x-cards.data-row :label="__('app.description')" :value="!empty($settlement->expense->description) ? $settlement->description : '--'"html="true"/>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">
                    @lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if ($settlement->status == 'draft')
                        <x-status :value="ucfirst($settlement->status)" color="grey" />
                    @elseif ($settlement->status == 'active')
                        <x-status :value="ucfirst($settlement->status)" color="light-blue" />
                    @elseif ($settlement->status == 'on progress')
                        <x-status :value="ucfirst($settlement->status)" color="yellow" />
                    @elseif ($settlement->status == 'approved')
                        <x-status :value="ucfirst($settlement->status)" color="light-green" />
                    @else
                        <x-status :value="ucfirst($settlement->status)" color="red" />
                    @endif
                </p>
            </div>

            @if ($settlement->status == 'approved')
                <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                    <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('modules.settlements.approvedBy')</p>
                    <p class="mb-0 text-dark-grey f-14"><x-employee :user="$settlement->approver" /></p>
                </div>
            @endif
            <x-forms.custom-field-show :fields="$fields" :model="$settlement"></x-forms.custom-field-show>
        </x-cards.data>
    </div>
</div>