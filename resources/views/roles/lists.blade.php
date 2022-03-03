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
                    

                    <div class="table-responsive">
                        @if (isset($roles) && $roles)
                            <table class="table table-bordered mb-0">
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
                                                    class="btn btn-default btn-sm">
                                                    <i class="fas fa-pencil"></i>
                                                </a>

                                                <a href="{{ route('roles.assign-perms', $values->id) }}"
                                                    title="Assign Permission" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-user-secret"></i>
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
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
@endsection
