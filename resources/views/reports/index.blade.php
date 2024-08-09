@extends('layouts.master')

@section('title')
    Reports
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Reports
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('reports.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                'PAYMENT_MANDATE' => 'Payment Mandate',
                                            ];
                                            $options = ['' => '-- Report Type --'] + $options;
                                        @endphp

                                        {{ Form::select('report_type', $options, old('report_type'), ['class' => 'form-control', 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                'EXCEL' => 'Excel',
                                                'PDF' => 'PDF',
                                            ];
                                            $options = ['' => '-- Report Format --'] + $options;
                                        @endphp

                                        {{ Form::select('report_format', $options, old('report_format'), ['class' => 'form-control', 'required=""']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at'), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at'), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $account_type_options = [];
                                            $options = [];
                                        @endphp
                                        @foreach ($account_types as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $account_type_options = $options;
                                            $options = ['' => '-- Payment Channel --'] + $options;
                                        @endphp

                                        {{ Form::select('account_type_id', $options, old('account_type_id'), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php $options = []; @endphp
                                        @foreach ($accounts as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Bank/MNO name --'] + $options;
                                        @endphp

                                        {{ Form::select('account_id', $options, old('account_id'), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="generate" class="btn btn-secondary">
                                                <i class="bx bx-export"></i> Generate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ./row -->
@endsection
@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

        });
    </script>
@endsection
