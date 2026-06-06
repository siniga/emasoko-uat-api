@extends('layouts.master')

@section('title')
    Users OTP
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/admin-directory.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="sp-list-shell admin-directory-shell">
        @component('components.breadcrumb')
            @slot('li_1')
                Users
            @endslot
            @slot('title')
                Users OTP
            @endslot
        @endcomponent

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Active OTPs</div>
                    <div class="detail-summary__value text-primary">{{ number_format($listStats['active']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Expiring Today</div>
                    <div class="detail-summary__value text-warning">{{ number_format($listStats['expiring_today']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Latest Created</div>
                    <div class="detail-summary__value text-draft">
                        {{ $listStats['latest_created_at'] ? date('d-m-Y H:i', strtotime($listStats['latest_created_at'])) : 'NIL' }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="detail-summary shadow h-96 py-2">
                    <div class="detail-summary__label text-muted">Latest Expiry</div>
                    <div class="detail-summary__value text-success">
                        {{ $listStats['latest_expire_at'] ? date('d-m-Y H:i', strtotime($listStats['latest_expire_at'])) : 'NIL' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card sp-list-card">
            <div class="card-body">
                @if (isset($users_otp) && $users_otp->count())
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="font-weight-500">No. of OTP records:
                                {{ number_format($users_otp->count()) }}</span>
                        </div>
                    </div>

                    <div class="table-responsive sp-list-table">
                        <table class="table table-hover table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="24%">User</th>
                                    <th width="16%">Phone</th>
                                    <th width="14%">OTP</th>
                                    <th width="18%">Created On</th>
                                    <th width="18%">Expires On</th>
                                    <th width="6%">State</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users_otp as $index => $values)
                                    @php
                                        $expiresAt = $values->expire_at
                                            ? \Carbon\Carbon::parse($values->expire_at)
                                            : null;
                                        $minutesLeft = $expiresAt ? now()->diffInMinutes($expiresAt, false) : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ optional($values->user)->name ?? 'NIL' }}<br>
                                            <span class="text-muted">{{ optional($values->user)->email ?? 'NIL' }}</span>
                                        </td>
                                        <td>{{ optional($values->user)->phone ?? 'NIL' }}</td>
                                        <td><span class="badge badge-soft-primary">{{ $values->otp }}</span></td>
                                        <td>{{ date('d-m-Y H:i', strtotime($values->created_at)) }}</td>
                                        <td>{{ $expiresAt ? $expiresAt->format('d-m-Y H:i') : 'NIL' }}</td>
                                        <td>
                                            @if (is_null($minutesLeft))
                                                <span class="badge badge-soft-secondary">Unknown</span>
                                            @elseif ($minutesLeft <= 10)
                                                <span class="badge badge-soft-warning">Expiring</span>
                                            @else
                                                <span class="badge badge-soft-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-danger mb-0">No active user OTP records found.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
