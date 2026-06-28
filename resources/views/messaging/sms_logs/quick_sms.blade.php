@extends('layouts.master')

@section('title')
    Send Quick SMS
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $title ?? 'SMS Logs' }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sms-logs') }}">SMS Logs</a></li>
                        <li class="breadcrumb-item active">Send Quick SMS</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row" x-data="initData()">
        <div class="col-lg-8 col-md-8 col-12">
            <div class="card">
                <div class="card-body">

                    @include('messages.alerts')

                    {{ Form::open(['url' => route('sms-logs.send-quick-sms'), 'method' => 'POST', 'class' => 'form-horizontal', 'autocomplete' => 'off']) }}
                    {{ Form::token() }}

                    <div class="row" x-data="fetchTemplates()">
                        <div class="col-md-12 col-12">
                            <div class="mb-3">
                                <label>Phone Number (s) <span class="text-danger">*</span></label>
                                {{ Form::text('phone', old('phone'), ['class="form-control"', 'placeholder="Write a phone number(s) in comma-separated..."']) }}
                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                            </div>
                        </div>

                        <div class="col-md-12 col-12">
                            <div class="mb-3">
                                <label>SMS Template</label>
                                <select name="template_id" x-ref="template_id" class="form-control select2"
                                    x-init="$nextTick(() => {
                                        const $el = $($refs.template_id).select2({ width: 'resolve', placeholder: '-- Select --' });
                                        // Bridge Select2 events back to Alpine
                                        $el.on('change', (e) => onTemplateChange(e.currentTarget.value));
                                    })" required>
                                    <option value="">-- Select --</option>
                                    <template x-for="(val, index) in templates" :key="index">
                                        <option x-bind:value="val.id">
                                            <span x-text="val.name"></span>
                                        </option>
                                    </template>
                                </select>
                                <span class="text-danger">{{ $errors->first('template_id') }}</span>
                            </div>
                        </div>
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-md-12 col-12">
                            <div class="mb-3">
                                <label>Message <span class="text-danger">*</span></label>
                                <textarea name="message" id="message" placeholder="Write message here..." class="form-control" rows="4"
                                    x-model="smsForm.message" required></textarea>
                                <span class="text-danger">{{ $errors->first('message') }}</span>
                            </div>
                        </div>
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label>Schedule</label><br />
                                {{ Form::checkbox('schedule', 1, old('schedule'), ['id' => 'schedule']) }} YES
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label>Schedule Date</label>
                                {{ Form::date('schedule_at', old('schedule_at'), ['class="form-control"', 'min' => date('Y-m-d')]) }}
                                <span class="text-danger">{{ $errors->first('schedule_at') }}</span>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label>Schedule Time</label>
                                @php
                                    $_options = [];
                                    $range = range(strtotime('06:00'), strtotime('23:00'), 30 * 60);
                                    foreach ($range as $time) {
                                        $_options[date('H:i', $time)] = date('H:i', $time);
                                    }
                                    $_options = ['' => '-- Select --'] + $_options;
                                @endphp
                                {{ Form::select('schedule_time', $_options, old('schedule_time'), ['class' => 'form-control']) }}
                                <span class="text-danger">{{ $errors->first('schedule_time') }}</span>
                            </div>
                        </div>
                    </div> <!-- /.row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-2">
                                <div class="text-sm-end d-none d-sm-block">
                                    <a href="{{ route('sms-logs') }}" class="btn btn-outline-danger px-4">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4">Send</button>
                                </div>
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
    @include('messaging.sms_logs.alpinejs')
@endsection
