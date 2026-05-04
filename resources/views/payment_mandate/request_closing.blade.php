@extends('layouts.master')

@section('title')
    Request Payment Mandate Closing
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/payment-mandate-requests.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="pmr-shell pmr-shell--closing">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Request Payment Mandate Closing</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payment-mandate.lists') }}">Payment Mandates</a></li>
                            <li class="breadcrumb-item active">Request Closing</li>
                        </ol>
                    </div>
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
                            Closing is intended for mandates that are ready to be finalized, not removed. Upload the validated references for the selected service provider and route them for approval.
                        </div>

                        {{ Form::open(['url' => route('payment-mandate.request-closing'), 'method' => 'POST', 'class' => 'form-horizontal', 'files' => true]) }}
                        {{ Form::token() }}

                        <div class="pmr-head">
                            <div>
                                <div class="pmr-head__title">Import Closing File</div>
                                <p class="pmr-head__hint">Select the provider, attach the spreadsheet, and submit mandates that need to be formally closed.</p>
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
                            <button type="submit" class="btn btn-warning">Import File</button>
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
