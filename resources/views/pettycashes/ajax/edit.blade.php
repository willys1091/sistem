@php
    $addPettycashCategoryPermission = user()->permission('manage_pettycash_category');
    $approvePettycashPermission = user()->permission('approve_pettycashes');
@endphp

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-pettycash-data-form">
            @method('PUT')
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.pettycashDetails')</h4>
                <div class="row p-20">
                    <div class="col-md-6 col-lg-4">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('modules.pettycashes.itemName')"
                            fieldName="item_name" fieldRequired="true" fieldId="item_name"
                            :fieldPlaceholder="__('placeholders.pettycash.item')" :fieldValue="$pettycash->item_name" />
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <input type="hidden" id="currency_id" name="currency_id" value="{{$pettycash->currency_id}}">
                        <x-forms.select :fieldLabel="__('modules.invoices.currency')" fieldName="currency"
                            fieldRequired="true" fieldId="currency">
                            @foreach ($currencies as $currency)
                                <option @selected($currency->id == $pettycash->currency_id)
                                        value="{{ $currency->id }}" data-currency-name="{{ $currency->currency_name }}">
                                    {{ $currency->currency_name }} - ({{ $currency->currency_symbol }})
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>
                    <input type = "hidden" name = "mention_user_ids" id = "mentionUserId" class ="mention_user_ids">

                    <div class="col-md-6 col-lg-2">
                        <x-forms.number fieldId="exchange_rate" :fieldLabel="__('modules.currencySettings.exchangeRate')"
                        fieldName="exchange_rate" fieldRequired="true" :fieldValue="$pettycash->exchange_rate" :fieldReadOnly="($companyCurrency->id == $pettycash->currency_id)"
                        :fieldHelp="$pettycash->currency->currency_name != company()->currency->currency_name ? '( '.$pettycash->currency->currency_name.' '.__('app.to').' '.company()->currency->currency_name.' )' : ' '"/>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.select fieldId="client_id" fieldName="client_id" :fieldLabel="__('app.client')" fieldRequired="true" search="true">
                            <option value="">--</option>
                            @foreach ($clients as $client)
                                <option @selected($client->id == $pettycash->client_id) value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.datepicker fieldId="purchase_date" fieldRequired="true"
                            :fieldLabel="__('modules.pettycashes.purchaseDate')" fieldName="purchase_date"
                            :fieldPlaceholder="__('placeholders.date')"
                            :fieldValue="$pettycash->purchase_date->format(company()->date_format)" />
                    </div>
                    <div class="col-md-4">
                        <x-forms.label class="mt-3" fieldId="category_id" :fieldLabel="__('modules.pettycashes.pettycashCategory')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="category_id" id="pettycash_category_id" data-live-search="true" disabled>
                                <option value="">--</option>
                                @foreach ($categories as $category)
                                    <option @selected($category->id == $pettycash->category_id) value="{{ $category->id }}">{{ $category->category_name }}</option>
                                @endforeach
                            </select>

                            @if ($addPettycashCategoryPermission == 'all' || $addPettycashCategoryPermission == 'added')
                                <x-slot name="append">
                                    <button id="addPettycashCategory" type="button"
                                        class="btn btn-outline-secondary border-grey"
                                        data-toggle="tooltip" data-original-title="{{__('modules.pettycashCategory.addPettycashCategory') }}">@lang('app.add')</button>
                                </x-slot>
                            @endif
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-6 col-lg-3 pettycash_price">
                        <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.amount')" fieldName="price" fieldRequired="true" fieldId="price" :fieldPlaceholder="__('placeholders.price')" :fieldValue="$pettycash->price" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.label class="mt-3" fieldId="urgency" :fieldLabel="__('app.urgency')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="urgency" id="urgency" data-live-search="true" data-size="8">
                                <option @selected($pettycash->urgency == 'normal') value="normal">Normal</option>
                                <option @selected($pettycash->urgency == 'urgent') value="urgent">Urgent</option>
                            </select>
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.label class="mt-3" fieldId="payment_type" :fieldLabel="__('app.paymentType')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="payment_type" id="payment_type" data-live-search="true" data-size="8">
                                <option @selected($pettycash->payment_type == 'transfer') value="transfer">Transfer</option>
                                <option @selected($pettycash->payment_type == 'cash') value="cash">Cash</option>
                            </select>
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-4">
                        <x-forms.text :fieldLabel="__('modules.pettycashes.purchaseFrom')" fieldName="purchase_from" fieldId="purchase_from" :fieldPlaceholder="__('placeholders.pettycash.vendor')" :fieldValue="$pettycash->purchase_from" />
                    </div>
                    <div class="col-md-4 payType">
                        <x-forms.text :fieldLabel="__('modules.pettycashes.payee')" fieldName="payee" fieldId="payee" :fieldValue="$pettycash->payee" fieldRequired="true"/>
                    </div>
                    <div class="col-md-6 col-lg-4 payType">
                        <x-forms.number class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.menu.bankaccount')" fieldName="bank_account" fieldRequired="true" fieldId="bank_account" :fieldValue="$pettycash->bank_account"/>
                    </div>
                    <div class="col-md-4 payType">
                        <x-forms.text :fieldLabel="__('modules.pettycashes.bankName')" fieldName="bank_name" fieldId="bank_name" :fieldValue="$pettycash->bank_name" fieldRequired="true"/>
                    </div>
                    @if (user()->permission('add_pettycashes') == 'all')
                        <div class="col-md-6 col-lg-4">
                            <x-forms.label class="mt-3" fieldId="user_id" :fieldLabel="__('app.employee')"></x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="user_id" id="user_id" data-live-search="true" data-size="8">
                                    <option value="">--</option>
                                    @foreach ($employees as $item)
                                        <x-user-option :user="$item" :selected="$item->id == $pettycash->user_id" />
                                    @endforeach
                                </select>
                            </x-forms.input-group>
                        </div>
                    @else
                        <input type="hidden" name="user_id" value="{{ user()->id }}">
                    @endif
                    @if(isset($projectName))
                        <div class="col-md-6 col-lg-4">
                            <input type="hidden" name="project_id" id="project_id" value="{{ $projectId }}">
                            <x-forms.text :fieldLabel="__('app.project')" fieldName="projectName" fieldId="projectName" :fieldValue="$projectName" fieldReadOnly="true" />
                        </div>
                    @else
                        @if (user()->permission('add_pettycashes') == 'all')
                            <div class="col-md-6 col-lg-4">
                                <x-forms.label class="mt-3" fieldId="project_id" :fieldLabel="__('app.project')"></x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="project_id" id="project_id" data-live-search="true" data-size="8">
                                        <option value="">--</option>
                                        @foreach ($projects as $project)
                                            <option data-currency-id="{{ $project->currency_id }}" @selected($project->id == $pettycash->project_id) value="{{ $project->id }}">
                                                {{ $project->project_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                            </div>
                        @else
                            <input type="hidden" name="project_id" value="">
                        @endif
                    @endif
                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="description" :fieldLabel="__('app.description')"></x-forms.label>
                            <div id="description">{!! $pettycash->description !!}</div>
                            <textarea name="description" id="description-text" class="d-none"></textarea>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <x-forms.file :fieldLabel="__('app.bill')" fieldRequired="true" fieldName="bill" fieldId="bill" :fieldValue="$pettycash->bill_url" allowedFileExtensions="txt pdf doc xls xlsx docx rtf png jpg jpeg svg" :popover="__('messages.fileFormat.multipleImageFile')" />
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-12">
                        @if (!is_null($pettycash->bill))
                            <x-file-card :fileName="$pettycash->bill" :dateAdded="$pettycash->created_at->diffForHumans()">
                                <i class="fa fa-file text-lightest"></i>
                                <x-slot name="action">
                                    <div class="dropdown ml-auto file-action">
                                        <button class="btn btn-lg f-14 p-0 text-lightest text-capitalize rounded  dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>

                                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0" aria-labelledby="dropdownMenuLink" tabindex="0">
                                            <a class="cursor-pointer d-block text-dark-grey f-13 py-3 px-3 " target="_blank" href="{{ $pettycash->bill_url }}">@lang('app.view')</a>
                                        </div>
                                    </div>
                                </x-slot>
                            </x-file-card>
                        @endif
                    </div>
                </div>
                <x-forms.custom-field :fields="$fields" :model="$pettycash"></x-forms.custom-field>
                <x-form-actions>
                    <x-forms.button-primary id="save-pettycash-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
                    <x-forms.button-cancel :link="route('pettycashes.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function() {
        if($('#payment_type').val() == 'cash') {
            $('#payee').val('-');
            $('#bank_account').val(0);
            $('#bank_name').val('-');
            $('.payType').hide();
        }else{
            $('.payType').show();
        }

        if('{{ $pettycash->category->is_detail }}' == '1') {
            $('#price').val(0);
            $('.pettycash_price').hide();
        }else{
             $('.pettycash_price').show();
        }
        
        if($('#project_id').val() != ''){
            $('#currency').prop('disabled', true);
        }

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        quillMention(null, '#description');

        const dp1 = datepicker('#purchase_date', {
            position: 'bl',
            dateSelected: new Date("{{ str_replace('-', '/', $pettycash->purchase_date) }}"),
            ...datepickerConfig
        });

        let categoryId = $('#pettycash_category_id').val();
        let userId = $('#user_id').val();
        getPettycashCategoryEmp(userId, categoryId);


        $('#user_id').change(function() {
            let userId = $(this).val();
            let categoryId = $('#pettycash_category_id').val();

            getEmployeeProjectCat(userId, categoryId);
        });

        $('#pettycash_category_id').change(function() {
            let categoryId = $(this).val();
            let userId = $('#user_id').val();
            getPettycashCategoryEmp(userId, categoryId);
        });

        $('#payment_type').change(function() {
            let type = $(this).val();
            if(type=='cash') {
                $('.payType').hide();
            }else{
                $('.payType').show();
            } 
        });

        function getEmployeeProjectCat(userId, categoryId) {
            const url = "{{ route('pettycashes.get_employee_projects') }}";

            $.easyAjax({
                url: url,
                type: "GET",
                data: {'userId' : userId, 'categoryId' : categoryId},
                success: function(response) {
                    $('#project_id').html('<option value="">--</option>'+response.data);
                    $('#project_id').selectpicker('refresh')
                    $('#pettycash_category_id').html('<option value="">--</option>'+response.category);
                    $('#pettycash_category_id').selectpicker('refresh')
                }
            });

        }

        function getPettycashCategoryEmp(userId, categoryId) {
            const url = "{{ route('pettycashes.get_category_employees') }}";

            $.easyAjax({
                url: url,
                type: "GET",
                data: {'categoryId' : categoryId, 'userId' : userId},
                success: function(response) {
                    $('#user_id').html('<option value="">--</option>'+response.employees);
                    $('#user_id').selectpicker('refresh')
                }
            });
        }

        $('#save-pettycash-form').click(function() {
            let note = document.getElementById('description').children[0].innerHTML;
            document.getElementById('description-text').value = note;
            var user = $('#description span[data-id]').map(function(){
                            return $(this).attr('data-id')
                        }).get();
            var mention_user_id  =  $.makeArray(user);
            $('#mentionUserId').val(mention_user_id.join(','));
            const url = "{{ route('pettycashes.update', $pettycash->id) }}";
            var data = $('#save-pettycash-data-form').serialize();

            $.easyAjax({
                url: url,
                container: '#save-pettycash-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-pettycash-form",
                data: data,
                file: true,
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });

        $('#addPettycashCategory').click(function() {
            const url = "{{ route('pettycashCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        <x-forms.custom-field-filejs/>

        init(RIGHT_MODAL);
    });

    $('body').on("change", '#currency, #project_id', function() {
        if ($('#project_id').val() != '') {
            var curId = $('#project_id option:selected').attr('data-currency-id');
            $('#currency').removeAttr('disabled');
            $('#currency').selectpicker('refresh');
            // $('#currency_id').val(curId);
            $('#currency').val(curId);
            $('#currency').prop('disabled', true);
            $('#currency').selectpicker('refresh');
        } else {
            $('#currency').prop('disabled', false);
            $('#currency').selectpicker('refresh');
        }

        var id = $('#currency').val();
        $('#currency_id').val(id);
        var currencyId = $('#currency_id').val();

        var companyCurrencyName = "{{$companyCurrency->currency_name}}";
        var currentCurrencyName = $('#currency option:selected').attr('data-currency-name');
        var companyCurrency = '{{ $companyCurrency->id }}';

        if(currencyId == companyCurrency){
            $('#exchange_rate').prop('readonly', true);
        } else{
            $('#exchange_rate').prop('readonly', false);
        }

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('payments.account_list') }}",
            type: "GET",
            blockUI: true,
            data: { 'curId' : currencyId , _token: token},
            success: function(response) {
                if (response.status == 'success') {
                    $('#bank_account_id').html(response.data);
                    $('#bank_account_id').selectpicker('refresh');
                    $('#exchange_rate').val(1/response.exchangeRate);
                    let currencyExchange = (companyCurrencyName != currentCurrencyName) ? '( '+currentCurrencyName+' @lang('app.to') '+companyCurrencyName+' )' : '';
                    $('#exchange_rateHelp').html('( '+currentCurrencyName+' @lang('app.to') '+companyCurrencyName+' )');
                }
            }
        });
    });

</script>
