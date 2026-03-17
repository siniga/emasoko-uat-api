@extends('layouts.master')

@section('title')
    Users OTP
@endsection

<style>
    .us-shell {
        --us-primary: #153567;
    }

    .us-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .us-table thead th {
        background: #edf4ff;
        color: var(--us-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }
</style>

@section('content')
    <div class="us-shell">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Users OTP</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Users OTP</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <h5 class="mb-1" style="color:#153567;">Active OTP Records</h5>
                    <p class="text-muted mb-0">This view shows OTP records that have not yet expired and are still relevant for current authentication support.</p>
                </div>

                <div class="table-responsive">
                    @if (isset($users_otp) && $users_otp)
                        <div class="table-responsive us-table">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="26%">User</th>
                                        <th width="18%">Phone</th>
                                        <th width="18%">OTP</th>
                                        <th width="18%">Created On</th>
                                        <th width="17%">Expires On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($users_otp as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ optional($values->user)->name ?? 'NIL' }}</td>
                                            <td>{{ optional($values->user)->phone ?? 'NIL' }}</td>
                                            <td><span class="badge badge-soft-primary">{{ $values->otp }}</span></td>
                                            <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                            <td>{{ date('d-m-Y H:i', strtotime($values->expire_at)) }}</td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">No user OTP created.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
