@extends('layouts.master')

@section('title')
    Collection Batches
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Collection Batches
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-12">
                            {!! Form::open(['url' => route('collections.batches'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-5 col-md-5 col-12">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>

                                <div class="col-lg-5 col-md-5 col-12">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>                               
                            
                                <div class="col-lg-2 col-md-2 col-12">
                                    <div class="mb-3">
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
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-md-12">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div>
                                @elseif(session()->get('warning'))
                                    <div class="alert alert-warning">
                                        {{ session()->get('warning') }}
                                    </div>
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">
                                        {{ session()->get('danger') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (isset($batches) && $batches->isNotEmpty())
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <span class="font-weight-500">No. of collection batches:
                                        {{ number_format($number_of_batches) }}</span>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-sm-end d-none d-sm-block">
                                        <a class="btn btn-primary btn-sm"
                                            href="{{ route('collections.create') }}">
                                            <i class="fas fa-plus"></i> Create Batch
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="20%">Service Provider</th>
                                            <th>Reference</th>
                                            <th>Processed</th>
                                            <th>No. of Success</th>
                                            <th>No. of Failed</th>
                                            <th>Created Date</th>
                                            <th>Updated Date</th>
                                            <th style="width: 20px; !important">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $serial = 1; @endphp
                                        @foreach ($batches as $values)
                                            <tr>
                                                <td>{{ $serial }}</td>
                                                <td>{{ $values->partner->business_name }}</td>
                                                <td>{{ $values->collectionReference ?? '' }}</td>
                                                <td>
                                                    @if($values->processed == 0)
                                                        <span class="badge badge-pill badge-soft-danger">No</span>
                                                    @elseif($values->processed == 1)
                                                        <span class="badge badge-pill badge-soft-success">Yes</span>
                                                    @endif
                                                </td>
                                                <td class="text-sm-end">{{ $values->success }}</td>
                                                <td class="text-sm-end">{{ $values->failed }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values->created_at)) }}</td>
                                                <td>{{ date('d-m-Y', strtotime($values->updated_at)) }}</td>

                                                <td>
                                                   <a href="{{ route('collections.show', $values->id) }}" title="View Batch"
                                                        class="btn btn-xss">
                                                        <i class="bx bxs-folder-open"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @php $serial++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-end">
                                    @if (method_exists($batches, 'links'))
                                        {{ $batches->links() }}
                                    @endif
                                </div>
                            </div>
                            <!--./d-flex -->
                        @else
                            <div class="alert alert-danger"> No any collection batches at the moment!</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection
@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //on select account category
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });

        });
    </script>
@endsection
