@extends('layouts.master')

@section('title')
    Payment Channel
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
        Dashboard
        @endslot
        @slot('title')
        Payment Channel
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
                                <a href="{{ route('payment-channels.create') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-plus"></i> Register New
                                </a>
                            </div>
                        </div>
                        <!--./col-md-12 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($accounts) && $accounts->isNotEmpty())
                            <table id="datatable" class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="12%">Type</th>
                                        <th width="20%">Name</th>
                                        <th width="30%">Description</th>
                                        <th width="16%">Created On</th>
                                        <th width="8%">Status</th>
                                        <th style="width: 60px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($accounts as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>{{ $values->accountType->name }}</td>
                                            <td>{{ $values->name }}</td>
                                            <td>{{ $values->description }}</td>
                                            <td>{{ $values->created_at }}</td>
                                            <td>
                                                @if ($values->active == 1)
                                                    <label class="badge bg-success">Active</label>
                                                @elseif ($values->active == 0)
                                                    <label class="badge bg-danger">Inactive</label>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('payment-channels.edit', $values->id) }}" title="Edit"
                                                    class="btn btn-xss">
                                                    <i class='bx bx-edit font-size-14'></i>
                                                </a>

                                                <a href="#"
                                                    title="Delete" class="btn btn-xss">
                                                    <i class="bx bx-trash text-danger font-size-14"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @php $serial++; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-danger"> No any payment channel created.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

@endsection

