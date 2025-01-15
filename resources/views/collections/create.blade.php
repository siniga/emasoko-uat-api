@extends('layouts.master')

@section('title')
    Create and Push Collection Batch
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Collection Batches
        @endslot
        @slot('title')
            Create and Push Collection Batch
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-12">
                            @if (session()->get('success'))
                                <div class="alert alert-success">
                                    {{ session()->get('success') }}
                                </div>
                            @elseif(session()->get('warning'))
                                <div class="alert alert-warning">
                                    {{ session()->get('warning') }}
                                </div>
                            @elseif(session()->get('danger'))
                                <div class="alert alert-danger">
                                    {{ session()->get('danger') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{ Form::open(['url' => route('collections.store'), 'method' => 'POST', 'class' => 'form-horizontal needs-validation', 'novalidate']) }}
                    {{ Form::token() }}
                        <section>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="mb-3">
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
                                            $options = ['' => 'Select'] + $options;
                                        @endphp

                                        {{ Form::select('provider_id', $options, old('provider_id', $provider->partner->id ?? ''), ['class' => 'form-control select2', 'id' => 'provider_id', 'x-model="form_data.partner_id"']) }}
                                        <span class="text-danger">{{ $errors->first('provider_id') }}</span>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="mb-3">
                                        <label for="created_at">Collection Date <span class="text-danger">*</span>
                                        </label>
                                        {{ Form::date('created_at', old('created_at', date('Y-m-d')), ['class="form-control"','placeholder="Enter collection date..."', 'max' => date('Y-m-d')]) }}
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <a href="{{ url('collections.batches') }}" class="btn btn-danger text-medium ">Cancel</a>
                                        <button type="submit" id="btnSubmit" class="btn btn-primary">
                                            Submit <i class="loading-spinner fa fa-lg fa fa-spinner fa-spin"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection