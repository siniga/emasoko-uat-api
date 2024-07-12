<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>CUSTOMER ACCOUNTS REPORT</title>

    <style>
        .invoice-box {
            width: 100%;
            margin: auto;
            padding: 30px;
            font-size: 12px;
            line-height: 12px;
            font-family: Arial, sans-serif !important;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            border-spacing: 0;
            line-height: inherit;
            text-align: left;
            border: none !important;
        }

        .invoice-box table th {
            font-size: 11px;
            line-height: 11px;
            text-align: left;
            vertical-align: middle;
            font-weight: 500 !important;
            color: #212121;
            padding: 4px;
            border: 0.01em solid #757575;
        }

        .invoice-box table td {
            vertical-align: top;
            font-size: 11px;
            line-height: 11px;
            color: #424242;
            font-weight: 400 !important;
            padding: 4px;
            border: 0.01em solid #757575;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="title" align="center">
            {{-- <img src="<?= base_url('assets/img/logo_icon.png') ?>" width="75"/> --}}
            <h2>eMakato Deduction Solution</h2>
            <h4>Customer Accounts Report</h4>
        </div>


        <table width="100%">
            <tr style="background-color: #ECEFF1;">
                <th width="3%">#</th>
                <th width="16%">Account Name</th>
                <th width="14%">NIDA Number</th>
                <th width="10%">Payment Channel</th>
                <th width="12%">Bank Name/MNO</th>
                <th width="12%">Account Number</th>
                <th width="10%">Created</th>
                <th width="10%">Status</th>
            </tr>

            @php $serial = 1; @endphp
            @foreach ($customer_accounts as $values)
                <tr>
                    <td>{{ $serial }}</td>
                    <td>
                        @if ($values->account_name == null)
                            {{ $values->phone }}
                        @else
                            {{ $values->account_name }}
                        @endif
                    </td>
                    <td>
                        <small>{{ $values->customer->id_number ?? 'NIN' }}</small>
                    </td>
                    <td>{{ $values->accountType->name ?? 'NIL' }}</td>
                    <td>{{ $values->account->name ?? 'NIL' }}</td>

                    <td>
                        {{ $values->account_number ?? $values->phone }}
                    </td>

                    <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>

                    <td>
                        @if ($values->status == 'partial')
                            <span class="badge bg-danger">Partial Approved</span>
                        @elseif($values->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($values->status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </table>
    </div>
</body>

</html>
