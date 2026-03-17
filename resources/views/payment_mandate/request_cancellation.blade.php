@extends('layouts.master')

@section('title')
    Request Payment Mandate Cancellation
@endsection

@section('content')
    <style>
        .pmr-shell {
            --pmr-primary: #153567;
            --pmr-danger: #b13a3a;
            --pmr-border: #dce6f5;
            --pmr-muted: #6b7c93;
        }

        .pmr-hero,
        .pmr-card {
            border: 1px solid var(--pmr-border);
            border-radius: 18px;
            box-shadow: 0 14px 34px rgba(29, 53, 87, 0.08);
        }

        .pmr-hero {
            background: linear-gradient(135deg, #7a1f1f 0%, #b13a3a 100%);
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .pmr-hero::after {
            content: '';
            position: absolute;
            inset: auto -32px -64px auto;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .pmr-hero .card-body,
        .pmr-card .card-body {
            padding: 1.15rem 1.2rem;
        }

        .pmr-kicker {
            font-size: 0.8rem;
            letter-spacing: 0.06em;
            opacity: 0.82;
            text-transform: uppercase;
        }

        .pmr-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0.2rem 0 0.3rem;
        }

        .pmr-subtitle {
            font-size: 0.88rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .pmr-chip-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 0.95rem;
        }

        .pmr-chip {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #fff;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .pmr-note {
            border: 1px dashed #e8c1c1;
            border-radius: 14px;
            background: #fff8f8;
            color: #8a4b4b;
            padding: 0.9rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .pmr-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pmr-head__title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--pmr-primary);
            margin-bottom: 0.2rem;
        }

        .pmr-head__hint {
            color: var(--pmr-muted);
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        .pmr-field label {
            display: block;
            color: var(--pmr-primary);
            font-weight: 600;
            margin-bottom: 0.35rem;
            font-size: 0.84rem;
        }

        .pmr-field .form-control {
            min-height: 40px;
            border-radius: 10px;
            border-color: #d7e3f4;
            box-shadow: none;
        }

        .pmr-field .form-control:focus {
            border-color: #9cb8df;
            box-shadow: 0 0 0 0.15rem rgba(21, 53, 103, 0.08);
        }

        .pmr-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.65rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
    </style>

    <div class="pmr-shell">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Request Payment Mandate Cancellation</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payment-mandate.lists') }}">Payment Mandates</a></li>
                            <li class="breadcrumb-item active">Request Cancellation</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card pmr-hero mb-4">
            <div class="card-body">
                <div class="pmr-kicker">Cancellation Workflow</div>
                <div class="pmr-title">Request Mandate Cancellation</div>
                <p class="pmr-subtitle">Upload a prepared spreadsheet of mandate references for the selected provider and submit them for cancellation review.</p>
                <div class="pmr-chip-wrap">
                    <span class="pmr-chip">Bulk Import</span>
                    <span class="pmr-chip">XLS/XLSX Template</span>
                    <span class="pmr-chip">Provider Scoped Validation</span>
                </div>
            </div>
        </div>

        <div class="row" x-data="initData();">
            <div class="col-lg-8 col-md-10 col-12">
                <div class="card pmr-card">
                    <div class="card-body">
                        @if (session()->get('success'))
                            <div class="alert alert-success mb-3">
                                {{ session()->get('success') }}
                            </div>
                        @endif

                        @if (session('file_errors'))
                            <div class="alert alert-danger mb-3">
                                @foreach (session('file_errors') as $row => $msgs)
                                    @foreach ($msgs as $msg)
                                        Row {{ $row }}: {{ $msg }}<br />
                                    @endforeach
                                @endforeach
                            </div>
                        @endif

                        <div class="pmr-note">
                            Use the template to upload mandate references exactly as issued in the system. Each reference is validated against the selected service provider before the cancellation requests are created.
                        </div>

                        {{ Form::open(['url' => route('payment-mandate.request-cancellation'), 'method' => 'POST', 'class' => 'form-horizontal', 'files' => true]) }}
                        {{ Form::token() }}

                        <div class="pmr-head">
                            <div>
                                <div class="pmr-head__title">Import Cancellation File</div>
                                <p class="pmr-head__hint">Select the provider first, then attach the spreadsheet that contains the mandate references to cancel.</p>
                            </div>

                            <div>
                                <a href="{{ asset('assets/files/Mandate_Reference_sample.xlsx') }}"
                                    class="btn btn-outline-primary btn-sm">Download XLS Template</a>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="pmr-field">
                                    <label>Service Provider <span class="text-danger">*</span></label>
                                    @php
                                        $options = [];
                                    @endphp
                                    @foreach ($providers as $val)
                                        @php
                                            $options[$val->id] = $val->business_name;
                                        @endphp
                                    @endforeach
                                    @php
                                        $options = ['' => '-- Service Provider --'] + $options;
                                    @endphp

                                    {{ Form::select('partner_id', $options, old('partner_id', $provider->partner->id ?? ''), ['class' => 'form-control select2', 'id' => 'partner_id', 'required' => '']) }}
                                    <span class="text-danger">{{ $errors->first('partner_id') }}</span>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="pmr-field">
                                    <label>Attachment XLS File <span class="text-danger">*</span></label>
                                    {{ Form::file('attachment', ['class' => 'form-control', 'required' => true, 'accept' => '.xls,.xlsx']) }}
                                    <span class="text-danger">{{ $errors->first('attachment') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pmr-actions">
                            <a href="{{ route('payment-mandate.lists') }}" class="btn btn-outline-danger">Cancel</a>
                            <button type="submit" class="btn btn-danger">Import File</button>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function initData() {
            return {}
        }
    </script>
@endsection
