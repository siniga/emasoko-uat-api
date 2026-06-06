@extends('layouts.master')

@section('title')
    SMS Logs
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $title ?? 'SMS Logs' }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">SMS Logs</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                     @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <div class="text-sm-end">
                                <div class="btn-group">
                                    <button class="btn btn-secondary btn-sm text-sm" type="button">
                                        Actions
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-secondary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="mdi mdi-chevron-down"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item text-sm" href="{{ route('sms-logs.quick-sms') }}">Quick SMS</a>
                                        <a class="dropdown-item text-sm" href="{{ route('sms-logs.file-sms') }}">File SMS</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-sm" href="{{ route('sms-templates') }}">SMS Templates</a>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="56%">Message</th>
                                    <th width="12%">Phone</th>
                                    <th width="10%">Sent Time</th>
                                    <th width="8%">No. of SMS</th>
                                    <th width="15%">Status</th>
                                    <th style="width: 80px !important;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @if ($sms_logs)
                                    @foreach ($sms_logs as $value)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $value->message }}</td>
                                            <td>{{ $value->phone }}</td>
                                            <td>{{ date('d-m-Y H:i', strtotime($value->created_at)) }}</td>
                                            <td class="text-end">{{ $value->number_of_sms }}</td>
                                            <td>{{ $value->status }}</td>
                                            <td class="justify-content-between">
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center">No records were found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-end">
                            @if (method_exists($sms_logs, 'links'))
                                {{ $sms_logs->links() }}
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection
