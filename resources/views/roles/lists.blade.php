@extends('layouts.master')

@section('title')
    Roles
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Roles
        @endslot
        @slot('title')
            Roles
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
                        <div class="col-md-12 col-12">
                            <div class="text-sm-end">
                                <a href="{{ route('roles.create') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-plus"></i> Create New
                                </a>
                            </div>
                        </div>
                        <!--./col-md-9 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($roles) && $roles)
                            <table id="datatable" class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="20%">Name</th>
                                        <th width="65%">Description</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($roles as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $values->name }}</td>
                                            <td>{{ $values->description }}</td>
                                            <td>
                                                <a href="{{ route('roles.edit', $values->id) }}" title="Edit"
                                                    class="btn btn-xss">
                                                    <i class='bx bx-edit font-size-14'></i>
                                                </a>

                                                <a href="{{ route('roles.assign-perms', $values->id) }}"
                                                    title="Assign Permission" class="btn btn-xss">
                                                    <i class="bx bxs-user-pin text-secondary font-size-14"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any role created.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

@endsection

