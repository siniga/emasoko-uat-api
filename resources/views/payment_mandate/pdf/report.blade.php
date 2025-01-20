<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            font-family: "Roboto, Cerebri, Sans-serif" !important;
        }

        @page {
            size: A4 landscape;
        }

        body {
            width: 100%;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            height: 32px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            color: #212121;
            padding: 8px;
            text-align: left;
        }
        th {
            font-size: 11px;
            background-color: #f4f4f4;
        }
        td {
            font-size: 10px;
        }
        h3 {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/images/emakato_logo_colored.png') }}" alt="Logo" class="logo">
        <div class="title">eMakato Payment Mandate Report</div>
    </div>

    <table>
        <thead>
            <tr style="background-color: #ECEFF1;">
                <th width="3%">#</th>
                <th width="20%">Account Name</th>
                <th width="10%">Bank Name/MNO</th>
                <th width="10%">Account Number</th>
                <th width="10%">Mandate Reference</th>
                <th width="10%">Primary Phone</th>
                <th width="10%">Mandate Status</th>
                <th width="10%">Created Date</th>
                <th width="10%">Created By</th>
            </tr>
        </thead>
        <tbody>
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
                            <span>PENDING</span>
                        @elseif($values->approved == 1)
                            <span>APPROVED</span>
                        @elseif($values->approved == 2)
                            <span>FAILED</span>
                        @endif
                    </td>

                    <td>{{ date('d-m-Y H:i', strtotime($values->updated_at)) }}</td>
                    <td>{{ $values->userCreated->name, }}</td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </tbody>
    </table>
</body>
</html>
