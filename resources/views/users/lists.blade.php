@extends('layouts.master')

@section('title')
    Users
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Users
        @endslot
        @slot('title')
            Users
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif

                    <div class="row mb-2">
                        <div class="col-md-9 col-12">
                            <div class="float-left">
                                <a href="{{ route('users.create') }}" class="btn btn-outline-primary btn-xs">
                                    <i class="fa fa-plus"></i> Register New
                                </a>
                            </div>
                        </div>
                        <!--./col-md-9 -->
    
                        <div class="col-md-3 col-12">
                            <div class="float-right">
                                <input type="text" id="myCustomSearchBox" class="form-control"
                            placeholder="Search details...">
                            </div>
                        </div>
                        <!--./col-md-4 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($users) && $users)
                            <table id="dt" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Name</th>
                                        <th width="16%">Email</th>
                                        <th width="12%">Phone</th>
                                        <th width="12%">Roles</th>
                                        <th width="12%">Registered On</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($users as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $values->name }}</td>
                                            <td>{{ $values->email }}</td>
                                            <td>{{ $values->phone }}</td>
                                            <td>
                                                @php $i = 1; @endphp
                                                @foreach ($values->roles as $role)
                                                    <small>{{ strtoupper($role->name) }}</small><br />
                                                    @php $i++; @endphp
                                                @endforeach
                                            </td>
                                            <td>{{ $values->created_at }}</td>
                                            <td>
                                                @if ($values->active == 1)
                                                    <label class="badge bg-success">Active</label>
                                                @elseif ($values->active == 0)
                                                    <label class="badge bg-danger">Inactive</label>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('users.edit', $values->id) }}" title="Edit"
                                                    class="btn btn-outline-secondary btn-xss">
                                                    <i class='bx bx-edit font-size-16'></i>
                                                </a>

                                                {{-- <a href="{{ route('users.change-status', $values->id) }}"
                                                    title="Change status" class="btn btn-outline-info btn-xs">
                                                    <i class="fa fa-info-circle"></i>
                                                </a> --}}

                                                {{-- <a href="{{ route('users.delete', $values->id) }}" title="Delete"
                                                    class="btn btn-outline-danger btn-xs delete">
                                                    <i class="fa fa-trash text-danger"></i>
                                                </a> --}}
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any user registered</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

@endsection
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
@endsection
