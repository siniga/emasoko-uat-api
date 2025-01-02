<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Payment Mandate Report</title>

    <style>
        * {
            font-family: "Roboto, Cerebri, Sans-serif" !important;
        }

        body {
            width: 100%;
        }

        h2 {
            font-size: 20px;
            margin-top: 0px;
            padding-top: 0px;
        }

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
    <header>
        <table class="table-header">
            <tbody>
                <tr>
                    <td>
                        <img src="{{ public_path('assets/images/emakato_logo_colored.png') }}" width="150px"
                            alt="Emakato">
                    </td>
                <tr>

                <tr>
                    <td>
                        <h4>eMakato Payment Mandate Report</h4>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="border-div"></div>
    </header>

    <div class="invoice-box">
        <table width="100%">
            <tr style="background-color: #ECEFF1;">
                <th width="3%">#</th>
                <th width="20%">Account Name</th>
                <th width="10%">Bank Name/MNO</th>
                <th width="10%">Account Number</th>
                <th width="10%">Mandate Reference</th>
                <th width="10%">Primary Phone</th>
                <th width="10%">Mandate Status</th>
                <th width="10%">Created Date</th>
            </tr>

            @php $serial = 1; @endphp
            @foreach ($debit_orders as $values)
                <tr>
                    <td>{{ $serial }}</td>
                    <td>
                        {{ strtoupper($values->customerAccount->account_name) ?? $values->customerAccount->phone }}<br>
                        ID: {{ $values->customerAccount->customer->id_number ?? 'NIL' }}
                    </td>
                    <td>{{ $values->customerAccount->account->name ?? '' }}</td>
                    <td>
                        {{ $values->customerAccount->account_number ?? $values->customerAccount->phone }}
                    </td>
                    <td>
                        {{ $values->reference }}
                    </td>
                    <td>{{ $values->customerAccount->phone ?? '' }}</td>

                    <td>
                        @if ($values->approved == 0)
                            <span>Pending</span>
                        @elseif($values->approved == 1)
                            <span>Approved</span>
                        @elseif($values->approved == 2)
                            <span>Failed</span>
                        @endif
                    </td>

                    <td>{{ date('d-m-Y H:i', strtotime($values->updated_at)) }}</td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </table>
    </div>
</body>

</html>
