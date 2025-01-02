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
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('users.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-12">
                                    <div class="mb-2">
                                        {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-3 col-12">
                                    <div class="mb-2">
                                        {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-3 col-12">
                                    <div class="mb-2">
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

                                        {{ Form::select('partner_id', $options, old('partner_id', $post_data['partner_id']), ['class' => 'form-control', 'id' => 'partner_id']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-3 col-12">
                                    <div class="mb-2">
                                        @php
                                            $options = [1 => 'Active', 0 => 'Inactive'];
                                            $options = ['' => '-- Status --'] + $options;
                                        @endphp

                                        {{ Form::select('status', $options, old('status', $post_data['status']), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="mb-2">
                                        {{ Form::text('keyword', old('keyword'), ['class="form-control"', 'placeholder="Search by name or phone..."']) }}
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="mb-2">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif

                    <div class="row mb-2">
                        <div class="col-md-12 col-12">
                            <div class="text-sm-end">
                                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus"></i> Register New
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        @if (isset($users) && $users)
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Name</th>
                                        <th width="14%">Email</th>
                                        <th width="10%">Phone</th>
                                        <th width="14%">Roles</th>
                                        <th width="10%">Created On</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 80px;">Actions</th>
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
                                                    {{ $role->description }}<br />
                                                    @php $i++; @endphp
                                                @endforeach
                                            </td>
                                            <td>{{ date('d-m-Y', strtotime($values->created_at)) }}</td>
                                            <td>
                                                @if ($values->active == 1)
                                                    <span class="badge bg-success-1 font-10">Active</span>
                                                @elseif ($values->active == 0)
                                                    <span class="badge bg-danger font-10">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('users.edit', $values->id) }}" title="Edit"
                                                    class="btn btn-xss">
                                                    <i class='bx bx-edit font-size-14'></i>
                                                </a>

                                                <a href="{{ route('users.change-status', $values->id) }}"
                                                    title="Change status" class="btn btn-xss">
                                                    <i class='bx bx-info-circle font-size-14 text-primary'></i>
                                                </a>

                                                <a href="{{ route('users.delete', $values->id) }}" title="Delete"
                                                    class="btn btn-xss delete">
                                                    <i class="bx bx-trash font-size-14 text-danger"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-end">
                                @if (method_exists($users, 'links'))
                                    {{ $users->links() }}
                                @endif
                            </div>
                        @else
                            <div class="alert alert-danger"> No any user registered</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
