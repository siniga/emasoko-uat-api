<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        {!! file_get_contents(public_path('assets/css/reports-pdf.css')) !!}
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
