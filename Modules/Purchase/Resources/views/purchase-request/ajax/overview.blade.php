<div class="row">
    <div class="col-sm-12">
         <x-cards.data :title="__('app.menu.expenses') . ' ' . __('app.details')" class=" mt-4">
            <x-cards.data-row :label="__('purchase::modules.purchaseRequest.code')" :value="$request->code" />
            <x-cards.data-row :label="__('purchase::modules.purchaseRequest.note')" :value="$request->note" />
            <x-cards.data-row :label="__('purchase::modules.purchaseRequest.requestDate')" :value="(!is_null($request->request_date) ? $request->request_date->translatedFormat(company()->date_format) : '--')" />
            <x-cards.data-row :label="__('purchase::modules.purchaseRequest.requestDate')" :value="(!is_null($request->estimation_delivery_date) ? $request->estimation_delivery_date->translatedFormat(company()->date_format) : '--')" />
            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 text-capitalize">@lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if ($request->status == 'draft')
                        <x-status :value="ucfirst($request->status)" color="grey" />
                    @elseif ($request->status == 'active')
                        <x-status :value="ucfirst($request->status)" color="light-blue" />
                    @elseif ($request->status == 'on progress')
                        <x-status :value="ucfirst($request->status)" color="yellow" />
                    @elseif ($request->status == 'approved')
                        <x-status :value="ucfirst($request->status)" color="light-green" />
                    @else
                        <x-status :value="ucfirst($request->status)" color="red" />
                    @endif
                </p>
            </div>
        </x-cards.data> 
    </div>
</div>