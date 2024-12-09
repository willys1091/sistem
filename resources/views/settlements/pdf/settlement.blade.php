<!DOCTYPE html>
<html>
<head>
    <title>{{ $header->code }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon/favicon.ico') }}" />
    <style>
        body {
		  font-size: 12px; font-family: "Calibri, sans-serif";margin:-25px; 
		}
        table {
            border-collapse: collapse;border-spacing: 0;padding-bottom:2px;
        }
        .bt{
            border-top:1px solid #000;
        }
        .bb{
            border-bottom:1px solid #000;
        }
        .bl{
            border-left:1px solid #000;
        }
        .br{
            border-right:1px solid #000;
        }
        .row{
            padding-top:5px;padding-bottom:5px;
        }
        .pl{
            padding-left:5px;
        }
        .pr{
            padding-right:5px;
        }
    </style>
</head>
<body>
    <div style="text-align:center;background-color:#d3d3d3;color:#fff;font-size: 20px;padding-top:5px;padding-bottom:5px;"><b>Settlement Form</b></div>
    <div>
        <table>
            <tr>
                <td width="80px" class="row pl bt bl">Number</td>
                <td width="1px" class="row bt">:</td>
                <td width="278px" class="row pl bt"><b>{{ $header->code }}</b></td>
                <td width="80px" class="row pl bt">Status</td>
                <td width="1px" class="row bt">:</td>
                <td width="278px" class="row pl bt br"><b>SELESAI</b></td>
            </tr>
            <tr>
                <td class="row pl bl">City</td>
                <td class="row">:</td>
                <td class="row pl"><b>JAKARTA</b></td>
                <td class="row pl">Date & Time</td>
                <td class="row">:</td>
                <td class="row pl br"><b>{{ date('d-m-Y H:i:s') }}</b></td>
            </tr>
            <tr>
                <td class="row pl bl bb">Venue</td>
                <td class="row bb">:</td>
                <td class="row pl bb"><b>AG27</b></td>
                <td class="row pl bb">Company</td>
                <td class="row bb">:</td>
                <td class="row pl br bb"><b>{{ $header->user->employeeDetail->company->company_name??'' }}</b></td>
            </tr>
        </table>
        <table>
            <tr>
                <td width="80px" class="row pl bt bl">Division</td>
                <td width="1px" class="row bt">:</td>
                <td width="278px" class="row pl bt"><b>{{ $header->user->employeeDetail->department->team_name }}</b></td>
                <td width="80px" class="row pl bt">Project/Unit</td>
                <td width="1px" class="row bt">:</td>
                <td width="278px" class="row pl bt br"><b>{{ $header->client->company_name??'-' }}</b></td>
            </tr>
            <tr>
                <td class="row pl bl">Name</td>
                <td class="row">:</td>
                <td class="row pl"><b>{{ ucwords($header->user->name) }}</b></td>
                <td class="row pl">Expenses No</td>
                <td class="row">:</td>
                <td class="row pl br"><b>{{ $header->expenses->expense_id??'' }}</b></td>
            </tr>
            <tr>
                <td class="row pl bl bb">Email</td>
                <td class="row bb">:</td>
                <td class="row pl bb"><b>{{ ucwords($header->user->email) }}</b></td>
                <td class="row pl bb">Urgency Level</td>
                <td class="row bb">:</td>
                <td class="row pl br bb"><b>{{ ucfirst($header->urgency) }}</b></td>
            </tr>
        </table>
        @if($header->is_detail==1)
            <table>
                <tr>
                    <td class="row pl bt bl br" colspan="6"><b>Detail {{ $header->category->name }}</b></td>
                </tr>
                <tr>
                    <td class="row bt bl br bb" colspan="6">
                        <table>
                        <tr>
                            <td width="25px" class="row pl" style="background-color:#d3d3d3;"><b>No</b></td>
                            <td width="303px" class="row pl" style="background-color:#d3d3d3;"><b>Description</b></td>
                            <td width="130px" class="row pl" style="background-color:#d3d3d3;"><b>Date</b></td>
                            <td width="120px" class="row pl" style="background-color:#d3d3d3;"><b>Category</b></td>
                            <td width="120px" class="row pl" style="text-align: center;background-color:#d3d3d3;"><b>Total</b></td>
                        </tr>
                        @php $x=1;$total=0; @endphp
                        @foreach($detail as $d)
                        <tr>
                            <td class="row pl">{{ $x }}</td>
                            <td class="row pl">{{ $d->remarks }}</td>
                            <td class="row pl">{{ date("d-m-Y",strtotime($d->estdate)) }}</td>
                            <td class="row pl">{{ $d->settlementCategoryDetail->category_name }}</td>
                            <td class="row pr" style="text-align: right;">{{ number_format($d->amount,0,'.',',') }}</td>
                        </tr>
                        @php $x++;$total+=$d->amount @endphp
                        @endforeach
                        <tr>
                            <td colspan="4" style="text-align: right;"><b>Total</b></td>
                            <td class="row pr" style="text-align: right;"><b>{{ number_format($total,0,'.',',') }}</b></td>
                           
                        </tr>
                    </table>
                    </td>
                </tr>
            </table>
        @endif
        <table>
            <tr>
                <td width="40px" class="row pl bt bl"></td>
                <td width="80px" class="row bt">Payee</td>
                <td width="1px" class="row pl bt">:</td>
                <td width="607px" class="row pl bt br"><b>{{ $header->payment_type=='cash' ? '' : $header->payee }}</b></td>
            </tr>
            <tr>
                <td class="row pl bl"></td>
                <td class="row">Total</td>
                <td class="row pl">:</td>
                <td class="row pl br"><b>{{ ucfirst($header->payment_type) }} {{ number_format($header->price,0,'.',',') }}</b></td>
            </tr>
            <tr>
                <td class="row pl bl {{ $header->payment_type=='cash'?'bb':'' }}"></td>
                <td class="row {{ $header->payment_type=='cash'?'bb':'' }}">In Words</td>
                <td class="row pl {{ $header->payment_type=='cash'?'bb':'' }}">:</td>
                <td class="row pl br {{ $header->payment_type=='cash'?'bb':'' }}"><b>{{ $inword }}</b></td>
            </tr>
            @if($header->payment_type<>'cash')
                <tr>
                    <td class="row pl bl"></td>
                    <td class="row">Bank Account</td>
                    <td class="row pl">:</td>
                    <td class="row pl br"><b>{{ $header->bank_account }}</b></td>
                </tr>
                <tr>
                    <td class="row pl bl bb"></td>
                    <td class="row bb">Bank</td>
                    <td class="row pl bb">:</td>
                    <td class="row pl br bb"><b>{{ $header->bank_name }}</b></td>
                </tr>
            @endif
        </table>
        <table>
            <tr>
                <td width="80px" class=" bt bl br bb" style="text-align: center;background-color:#d3d3d3; padding-top:25px;padding-bottom:25px;">Remarks</td>
                <td width="662px" class="pl bt br bb"><b>{!! $header->description !!}</b></td>
            </tr>
        </table>
        <table>
            <tr>
                <td class="row pl bt bl br" colspan="6"><b>History Tracking</b></td>
            </tr>
            <tr>
                <td class="row bt bl br bb" colspan="6">
                    <table>
                    <tr>
                        <td width="25px" class="row pl" style="background-color:#d3d3d3;"><b>No</b></td>
                        <td width="150px" class="row pl" style="background-color:#d3d3d3;"><b>Email</b></td>
                        <td width="120px" class="row pl" style="background-color:#d3d3d3;"><b>Date</b></td>
                        <td width="120px" class="row pl" style="background-color:#d3d3d3;"><b>Status</b></td>
                        <td width="303px" class="row pl" style="background-color:#d3d3d3;"><b>Remarks</b></td>
                    </tr>
                    @php $y=1; @endphp
                    @foreach($approval as $a)
                    <tr>
                        <td class="row pl">{{ $y }}</td>
                        <td class="row pl">{{ ucwords($a->user->name??'') }}</td>
                        <td class="row pl">{{ $a->approval_date?date("d-m-Y h:i:s",strtotime($a->approval_date)):'' }}</td>
                        <td class="row pl">{{ $a->status }}</td>
                        <td class="row pr">{!! $a->remarks !!}</td>
                    </tr>
                    @php $y++; @endphp
                    @endforeach
                </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>