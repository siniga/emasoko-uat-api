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
                                <a href="{{ route('payment-channels.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bx bx-list-plus"></i> Create New
                                </a>
                            </div>
                        </div>
                        <!--./col-md-12 -->
                    </div>
                    <!--./row -->

                    <div class="table-responsive">
                        @if (isset($accounts) && $accounts->isNotEmpty())
                            <table class="table table-bordered dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th>Logo</th>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Created On</th>
                                        <th>Status</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach ($accounts as $values)
                                        <tr>
                                            <td>{{ $serial }}</td>
                                            <td>
                                                @if (!empty($values->logo))
                                                    <img src="{{ asset($values->logo) }}" alt="{{ $values->name }}" class="img-thumbnail" style="max-height: 40px;">
                                                @else
                                                    <span class="text-muted">No logo</span>
                                                @endif
                                            </td>
                                            <td>{{ $values->accountType->name }}</td>
                                            <td>{{ $values->name }}</td>
                                            <td>{{ $values->slug }}</td>
                                            <td>{{ $values->created_at }}</td>
                                            <td>
                                                @if ($values->active == 1)
                                                    <span class="badge bg-success-1 font-size-10">Active</span>
                                                @elseif ($values->active == 0)
                                                    <span class="badge bg-danger font-size-10">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('payment-channels.edit', $values->id) }}" title="Edit"
                                                    class="btn btn-xss">
                                                    <i class='bx bx-edit font-size-14'></i>
                                                </a>

                                                <a href="{{ route('payment-channels.delete', $values->id) }}"
                                                    title="Delete" class="btn btn-xss delete">
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
