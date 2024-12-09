@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <div class="d-flex d-lg-block filter-box project-header bg-white">
        <div class="mobile-close-overlay w-100 h-100" id="close-client-overlay"></div>
        <div class="project-menu" id="mob-client-detail">
            <a class="d-none close-it" href="javascript:;" id="close-client-detail"><i class="fa fa-times"></i></a>
            <nav class="tabs">
                <ul class="-primary">
                    <li><x-tab :href="route('fa-report.index')" :text="__('app.menu.expenses')" class="expenses"/></li>
                    <li><x-tab :href="route('fa-report.index').'?tab=settlements'" :text="__('app.menu.settlements')" ajax="false" class="settlements"/></li>
                    <li><x-tab :href="route('fa-report.index').'?tab=reimbursements'" :text="__('app.menu.reimbursements')" ajax="false" class="reimbursements"/></li>
                    <li><x-tab :href="route('fa-report.index').'?tab=pettyCashes'" :text="__('app.menu.pettycashes')" ajax="false" class="pettyCashes"/></li>
                </ul>
            </nav>
        </div>
        <a class="mb-0 d-block d-lg-none text-dark-grey ml-auto mr-2 border-left-grey" onclick="openClientDetailSidebar()"><i class="fa fa-ellipsis-v "></i></a>
    </div>
@endsection

@section('content')
    <div class="content-wrapper pt-0 border-top-0 client-detail-wrapper">@include($view)</div>
@endsection

@push('scripts')
    <script>
        const activeTab = "{{ $activeTab??'' }}";
        $('.project-menu .' + activeTab).addClass('active');
    </script>
@endpush