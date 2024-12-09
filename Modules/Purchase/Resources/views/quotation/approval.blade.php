<div class="modal-header">
    <h5 class="modal-title">Approval List</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered table-responsive-sm table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Approver</th>
                            <th>Approval Date</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $x=1; @endphp
                        @foreach($approval as $a)
                            <tr>
                                <td>{{ $x }}</td>
                                <td>{{ $a->user->name }}<br>{{ $a->user->email }}</td>
                                <td>{{ $a->approval_date??'-' }}</td>
                                <td>{!! $a->remarks??'-' !!}</td>
                                <td>{!! (($a->status=='approve')?'<span class="badge badge-success">Approve</span':(($a->status=='decline')?'<span class="badge badge-danger">decline</span':'-')) !!}</td>
                            </tr>
                            @php $x++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
</div>
