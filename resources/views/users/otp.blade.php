@extends('layouts.master')

@section('title')
    Users
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Users OTP
        @endslot
        @slot('title')
            Users OTP
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        @if (isset($users_otp) && $users_otp)
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Name</th>
                                        <th width="12%">Phone</th>
                                        <th width="13%">OTP</th>
                                        <th width="12%">Created On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($users_otp as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $values->user->name }}</td>
                                            <td>{{ $values->user->phone }}</td>
                                            <td>{{ $values->otp }}</td>
                                            <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any user otp created</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

