@extends('layouts.master')

@section('title')
    Request Payment Mandate Cancellation
@endsection

@section('content')
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

    <div class="row" x-data="initData();">
        <div class="col-lg-8 col-md-8 col-sm-8 col-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @endif

                    @if (session('file_errors'))
                        <div class="alert alert-danger">
                            @foreach (session('file_errors') as $row => $msgs)
                                @foreach ($msgs as $msg)
                                    Row {{ $row }}: {{ $msg }}<br />
                                @endforeach
                            @endforeach
                        </div>
                    @endif

                    {{ Form::open(['url' => route('payment-mandate.request-cancellation'), 'method' => 'POST', 'class' => 'form-horizontal', 'files' => true]) }}
                    {{ Form::token() }}

                    <div class="row my-2">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                            <h6 class="text-uppercase font-weight-600">Import XLS File</h6>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                            <div class="text-sm-end">
                                <a href="{{ asset('assets/files/Mandate_Reference_sample.xlsx') }}"
                                    class="text-sm text-primary">Download XLS Template</a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                            <div class="mb-3">
                                <label>Service Provider <span class="red">*</span></label>
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

                        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
                            <div class="mb-3">
                                <label>Attachment XLS File <span class="red">*</span></label>
                                {{ Form::file('attachment', ['class' => 'form-control', 'required' => true, 'accept' => '.xlsx']) }}
                                <span class="text-danger">{{ $errors->first('attachment') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3 text-sm-end d-none d-sm-block">
                                <a href="{{ route('payment-mandate.lists') }}"
                                    class="btn btn-danger text-medium">Cancel</a>
                                <button type="submit" class="btn btn-primary text-medium">Import File</button>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
            }
        }
    </script>
@endsection
