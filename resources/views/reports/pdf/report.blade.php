<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 22px 24px 28px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        .page-shell {
            width: 100%;
        }

        .report-header {
            width: 100%;
            border-bottom: 2px solid #153567;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .report-header-table {
            width: 100%;
            border: 0;
        }

        .report-header-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .brand-block {
            width: 220px;
        }

        .brand-logo {
            height: 48px;
            margin-bottom: 8px;
        }

        .brand-name {
            font-size: 15px;
            font-weight: bold;
            color: #153567;
            margin-bottom: 3px;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #64748b;
            line-height: 1.45;
        }

        .report-meta-card {
            width: 235px;
            margin-left: auto;
            border: 1px solid #d7e2f1;
            background: #f8fbff;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .report-meta-title {
            font-size: 12px;
            font-weight: bold;
            color: #153567;
            margin-bottom: 8px;
        }

        .report-meta-row {
            margin-bottom: 4px;
            font-size: 9.5px;
            color: #475569;
        }

        .report-meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            display: inline-block;
            width: 92px;
            font-weight: bold;
            color: #153567;
        }

        .report-title-bar {
            width: 100%;
            border: 1px solid #dce6f5;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .report-title {
            font-size: 17px;
            font-weight: bold;
            color: #153567;
            margin-bottom: 4px;
        }

        .report-subtitle {
            font-size: 10px;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 0;
        }

        .report-summary {
            width: 100%;
            border: 1px solid #dce6f5;
            background: #f8fbff;
            border-radius: 10px;
            padding: 9px 12px;
            margin-bottom: 12px;
            font-size: 9.5px;
            color: #475569;
        }

        .summary-highlight {
            font-weight: bold;
            color: #153567;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
        }

        th {
            background: #153567;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            font-size: 9px;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        td {
            font-size: 9px;
            line-height: 1.35;
        }

        .report-footer {
            margin-top: 10px;
            font-size: 8.5px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('assets/images/emakato_logo_colored.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    @endphp

    <div class="page-shell">
        <div class="report-header">
            <table class="report-header-table">
                <tr>
                    <td class="brand-block">
                        @if ($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="eMakato Logo" class="brand-logo">
                        @endif
                        <div class="brand-name">eMakato Report</div>
                        <div class="brand-subtitle">
                            Direct debit operations, collection performance,
                            and mandate intelligence generated from the core reporting module.
                        </div>
                    </td>
                    <td>
                        <div class="report-meta-card">
                            <div class="report-meta-title">Report Metadata</div>
                            <div class="report-meta-row">
                                <span class="meta-label">Report:</span> {{ $title }}
                            </div>
                            <div class="report-meta-row">
                                <span class="meta-label">Generated:</span> {{ now()->format('d-m-Y H:i') }}
                            </div>
                            <div class="report-meta-row">
                                <span class="meta-label">Rows:</span> {{ number_format(count($rows)) }}
                            </div>

                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headings) }}">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="report-footer">
            eMakato System Report
        </div>
    </div>
</body>
</html>
