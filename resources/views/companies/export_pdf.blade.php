<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>COMPANIES REPORT</title>

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
            <h4>Companies Report</h4>
        </div>


        <table width="100%">
            <tr style="background-color: #ECEFF1;">
                <th width="2%">#</th>
                <th width="14%">Bussiness Name</th>
                <th width="10%">TIN Number</th>
                <th width="8%">Category</th>
                <th width="8%">Year of Incorporation</th>
                <th width="8%">No. of staff</th>
                <th width="14%">Company Contact</th>
                <th width="10%">Region</th>
                <th width="10%">Physical Address</th>
                <th width="10%">Created Date</th>
            </tr>

            @php $serial = 1; @endphp
            @foreach ($companies as $company)
                <td>{{ $serial }}</td>
                <td>{{ $company->business_name }}</td>
                <td>{{ $company->tin_number }}</td>
                <td>{{ $company->partnerCategory->name ?? '' }}</td>
                <td>{{ $company->year_of_incorporation }}</td>
                <td>{{ $company->number_of_staff ?? '' }}</td>
                <td>{{ $company->email ?? '' }}<br />
                {{ $company->phone ?? '' }}</td>
                <td>{{ $company->region->name ?? '' }}</td>
                <td>{{ $company->physical_address ?? '' }}</td>
                <td>{{ $company->created_at }}</td>
                @php $serial++; @endphp
            @endforeach
        </table>
    </div>
</body>

</html>
