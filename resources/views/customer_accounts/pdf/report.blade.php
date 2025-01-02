<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            font-family: "Roboto, Cerebri, Sans-serif" !important;
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
        <div class="title">Customer Accounts Report</div>
    </div>

    <table>
        <thead>
            <tr style="background-color: #ECEFF1;">
                <th width="3%">#</th>
                <th width="30%">Account Name</th>
                <th>Bank Name/MNO</th>
                <th>Account Number</th>
                <th>Phone</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @php $serial = 1; @endphp
            @foreach ($customer_accounts as $values)
                <tr>
                    <td>{{ $serial }}</td>
                        <td>
                            {{ strtoupper($values->account_name) ?? $values->phone }}<br>
                            ID: {{ $values->customer->id_number ?? 'NIL' }}
                        </td>
                        <td>{{ $values->account->name ?? 'NIL' }}</td>
                        <td>{{ $values->account_number ?? $values->phone }}</td>
                        <td>{{ $values->phone }}</td>
                        <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </tbody>
    </table>
</body>
</html>
