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
            <h1>eMakato</h1>
            <h3></h3>
            <h4>Companies Report</h4>
        </div>


        <table width="100%">
            <tr style="background-color: #ECEFF1;">
                <th width="2%">#</th>
                <th width="15%">Bussiness Name</th>
                <th width="10%">Category</th>
                <th width="5%">Year</th>
                <th width="12%">Email</th>
                <th width="10%">Phone</th>
                <th width="10%">Region</th>
                <th width="12%">Physical Address</th>
                <th width="6%">No. Staff</th>
                <th width="10%">Reg. Date</th>
            </tr>

            @php $serial = 1; @endphp
            @foreach ($companies as $company)
                <tr>
                    <td>{{ $serial }}</td>
                    <td>{{ $company->business_name }}
                        <br />{{ $company->tin_number }}
                    </td>
                    <td>{{ $company->partnerCategory->name ?? '' }}</td>
                    <td>{{ $company->year_of_incorporation }}</td>
                    <td>{{ $company->email ?? '' }}</td>
                    <td>{{ $company->phone ?? '' }}</td>
                    <td>{{ $company->region->name ?? '' }}</td>
                    <td>{{ $company->physical_address ?? '' }}</td>
                    <td>{{ $company->number_of_staff ?? '' }}</td>
                    <td>{{ $company->created_at }}</td>
                </tr>
                @php $serial++; @endphp
            @endforeach
        </table>
    </div>
</body>

</html>
