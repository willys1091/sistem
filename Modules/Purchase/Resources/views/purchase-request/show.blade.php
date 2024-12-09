@extends('layouts.app')

@push('datatable-styles') @include('sections.datatable_css')@endpush
@section('filter-section')
    <div class="d-flex d-lg-block filter-box project-header bg-white">
        <div class="mobile-close-overlay w-100 h-100" id="close-client-overlay"></div>
        <div class="project-menu" id="mob-client-detail">
            <a class="d-none close-it" href="javascript:;" id="close-client-detail"><i class="fa fa-times"></i></a>
            @if(isset($request->id))
                <nav class="tabs">
                    <ul class="-primary">
                        <li><x-tab :href="route('purchase-request.show', $request->id)" :text="__('purchase::modules.purchaseRequest.overview')" class="overview"/></li>
                        <li><x-tab :href="route('purchase-request.show', $request->id).'?tab=detail'" :text="__('purchase::modules.purchaseRequest.detail')" ajax="false" class="detail"/></li>
                    </ul>
                </nav>
            @endif
        </div>
        <a class="mb-0 d-block d-lg-none text-dark-grey ml-auto mr-2 border-left-grey" onclick="openClientDetailSidebar()"><i class="fa fa-ellipsis-v "></i></a>
    </div>
@endsection

@section('content')
    <div class="content-wrapper pt-0 border-top-0 client-detail-wrapper">@include($view)</div>
@endsection

@push('scripts')
<script>
    $("body").on("click", ".project-menu .ajax-tab", function(event) {
        event.preventDefault();

        $('.project-menu .p-sub-menu').removeClass('active');
        $(this).addClass('active');

        const requestUrl = this.href;

        $.easyAjax({
            url: requestUrl,
            blockUI: true,
            container: ".content-wrapper",
            historyPush: true,
            success: function(response) {
                if (response.status == "success") {
                    $('.content-wrapper').html(response.html);
                    init('.content-wrapper');
                }
            }
        });
    });
</script>
    <script>
        const hasRequest = "{!! isset($request->id)?'1':'0' !!}";
        if (hasRequest == '1'){
            const activeTab = "{{ $activeTab??'' }}";
            $('.project-menu .' + activeTab).addClass('active');
        }
    </script>
@endpush

