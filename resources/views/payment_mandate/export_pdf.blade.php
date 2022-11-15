<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>PAYMENT MANDATE REPORT</title>

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
            <h4>Payment Mandate Report</h4>
        </div>


        <table width="100%">
            <tr style="background-color: #ECEFF1;">
                <th width="3%">#</th>
                <th width="18%">Account Name</th>
                <th width="15%">NIDA Number</th>
                <th width="8%">Payment Channel</th>
                <th width="10%">Bank Name/MNO</th>
                <th width="12%">Account Number</th>
                <th width="12%">Phone<br /><small>(Connected to Bank)</small></th>
                <th width="8%">Consent Status</th>
            </tr>

            @php $serial = 1; @endphp
            @foreach ($debit_orders as $values)
                <tr>
                    <td>{{ $serial }}</td>
                    <td>{{ $values->customerAccount->account_name ?? $values->customerAccount->phone }}
                    </td>
                    <td><small>{{ $values->customerAccount->customer->id_number ?? 'NIL' }}</small>
                    </td>
                    <td>{{ $values->customerAccount->accountType->name ?? '' }}</td>
                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                    <td>
                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                    </td>
                    <td>{{ $values->customerAccount->phone ?? '' }}</td>

                    <td>
                        @if ($values->approved == 0)
                            <span class="badge badge-pill badge-soft-danger">Pending</span>
                        @elseif($values->approved == 1)
                            <span class="badge badge-pill badge-soft-success">Approved</span>
                        @elseif($values->approved == 99)
                            <span class="badge badge-pill badge-soft-danger">Rejected</span>
                        @endif
                    </td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </table>
    </div>
</body>

</html>
