@extends('layouts.master')

@section('title')
    Service Providers
@endsection

<style>
    .sp-list-shell {
        --sp-list-primary: #153567;
        --sp-list-border: #dce6f5;
        --sp-list-muted: #6b7c93;
    }

    .sp-list-summary {
        border: 1px solid var(--sp-list-border);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        padding: 1rem;
        height: 100%;
    }

    .sp-list-summary__label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--sp-list-muted);
        margin-bottom: 0.35rem;
    }

    .sp-list-summary__value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--sp-list-primary);
    }

    .sp-list-filter {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .sp-list-filter__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--sp-list-primary);
        margin-bottom: 0.2rem;
    }

    .sp-list-filter__hint {
        color: var(--sp-list-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .sp-list-card {
        border: 1px solid var(--sp-list-border);
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .sp-list-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #dfe8f4;
    }

    .sp-list-table thead th {
        background: #edf4ff;
        color: var(--sp-list-primary);
        font-weight: 700;
        border-color: #dce7fa;
    }

    .sp-row-actions {
        position: relative;
    }

    .sp-row-actions .dropdown-toggle {
        border: 0;
        background: transparent;
        color: var(--sp-list-primary);
        border-radius: 999px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .sp-row-actions .dropdown-toggle:hover,
    .sp-row-actions .dropdown-toggle:focus {
        background: #edf4ff;
        color: var(--sp-list-primary);
        box-shadow: none;
        border-color: transparent;
    }

    .sp-row-actions .dropdown-toggle::after {
        display: none;
    }

    .sp-row-actions .dropdown-menu {
        min-width: 10rem;
        border: 1px solid #dfe8f4;
        box-shadow: 0 16px 30px rgba(21, 53, 103, 0.12);
        border-radius: 12px;
        padding: 0.35rem 0;
    }

    .sp-row-actions .dropdown-item {
        font-size: 0.875rem;
        padding: 0.55rem 0.9rem;
    }
</style>

@section('content')
    <div class="sp-list-shell">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Service Provider</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Service Providers</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="sp-list-filter mb-4">
                    <div class="sp-list-filter__title">Filter Service Providers</div>
                    <div class="sp-list-filter__hint">Narrow the service provider portfolio by date, category, region, approval state, account verification status, or search keyword.</div>
                    <div class="row">
                        <div class="col-lg-12">
                            {!! Form::open(['url' => route('companies.lists'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}
                            {{ Form::token() }}

                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('start_at', old('start_at', $post_data['start_at']), ['placeholder="Start Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        {{ Form::date('end_at', old('end_at', $post_data['end_at']), ['placeholder="End Date..."', 'class="form-control"']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        @php
                                            $options = [];
                                        @endphp
                                        @foreach ($categories as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Select Category --'] + $options;
                                        @endphp

                                        {{ Form::select('category_id', $options, old('category_id', $post_data['category_id']), ['class' => 'form-control', 'id' => 'category_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php $options = []; @endphp
                                        @foreach ($regions as $val)
                                            @php
                                                $options[$val->id] = $val->name;
                                            @endphp
                                        @endforeach
                                        @php
                                            $options = ['' => '-- Select Region --'] + $options;
                                        @endphp

                                        {{ Form::select('region_id', $options, old('region_id', $post_data['region_id']), ['class' => 'form-control', 'id' => 'region_id']) }}
                                    </div>
                                </div>
                                <!--./col -->

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                0 => 'Pending Review',
                                                1 => 'Approved',
                                                99 => 'Failed',
                                            ];
                                            $options = ['' => '-- Select eMakato Status --'] + $options;
                                        @endphp

                                        {{ Form::select('approved', $options, old('approved', $post_data['approved']), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <!--./col -->
                            </div>
                            <!--./row -->

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        {{ Form::text('keyword', old('keyword', $post_data['keyword']), ['class="form-control"', 'placeholder="Search by business name  or TIN number..."']) }}
                                    </div>
                                </div>
                                <!--./col -->
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        @php
                                            $options = [
                                                1 => 'Verified',
                                                99 => 'With Rejected Accounts',
                                            ];
                                            $options = ['' => '-- Select Account Status --'] + $options;
                                        @endphp

                                        {{ Form::select('is_verified', $options, old('is_verified', $post_data['is_verified']), ['class' => 'form-control']) }}
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <div class="text-sm-end d-none d-sm-block">
                                            <button type="submit" name="filter" class="btn btn-secondary">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-2">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <div>
                                        <div class="sp-list-filter__title mb-1">Filtered Summary</div>
                                        <div class="sp-list-filter__hint mb-0">These numbers follow the current filters.</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="sp-list-summary">
                                            <div class="sp-list-summary__label">Total Providers</div>
                                            <div class="sp-list-summary__value">{{ number_format($companyStats['total']) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="sp-list-summary">
                                            <div class="sp-list-summary__label">Approved</div>
                                            <div class="sp-list-summary__value text-success">{{ number_format($companyStats['approved']) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="sp-list-summary">
                                            <div class="sp-list-summary__label">Pending</div>
                                            <div class="sp-list-summary__value text-warning">{{ number_format($companyStats['pending']) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="sp-list-summary">
                                            <div class="sp-list-summary__label">Failed</div>
                                            <div class="sp-list-summary__value text-danger">{{ number_format($companyStats['failed']) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="sp-list-summary">
                                            <div class="sp-list-summary__label">Verified Accounts</div>
                                            <div class="sp-list-summary__value text-info">{{ number_format($companyStats['verified_accounts']) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
            </div>

            <div class="card sp-list-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-md-12">
                                @if (session()->get('success'))
                                    <div class="alert alert-success">
                                        {{ session()->get('success') }}
                                    </div>
                                @elseif(session()->get('danger'))
                                    <div class="alert alert-danger">
                                        {{ session()->get('danger') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-6">
                                <span class="font-weight-500">No. of service providers:
                                    {{ number_format($number_of_companies) }} </span>
                            </div>

                            <div class="col-md-6">
                                <div class="text-sm-end d-none d-sm-block">
                                    <a class="btn btn-primary btn-sm" href="{{ route('companies.registration') }}">
                                        <i class="bx bx-list-plus"></i> Register New
                                    </a>
                                    @if (isset($companies) && $companies->isNotEmpty())
                                        <a class="btn btn-outline-success btn-sm"
                                            href="{{ route('companies.export-xls', ['start_at' => $post_data['start_at'], 'end_at' => $post_data['end_at'], 'category_id' => $post_data['category_id'], 'region_id' => $post_data['region_id'], 'approved' => $post_data['approved'], 'is_verified' => $post_data['is_verified'], 'keyword' => $post_data['keyword']]) }}">
                                            <i class="fas fa-file-excel"></i> Export XLS
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if (isset($companies) && $companies->isNotEmpty())
                            <div class="table-responsive sp-list-table">
                                <table class="table table-bordered dt-responsive  nowrap w-100" id="dynamicTable">
                                    <thead>
                                        <tr>
                                            <th width="3%">#</th>
                                            <th width="24%">Bussiness Name</th>
                                            <th>Category</th>
                                            <th>TIN Number</th>
                                            <th>Year of Inc</th>
                                            <th>Phone</th>
                                            <th>Region</th>
                                            <th>Approved</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $serial = 1; @endphp
                                        @foreach ($companies as $company)
                                            <tr>
                                                <td>{{ $serial }}</td>
                                                <td>{{ $company->business_name }}</td>
                                                <td>{{ $company->partnerCategory->name ?? '' }}</td>
                                                <td>{{ $company->tin_number }}</td>
                                                <td>{{ $company->year_of_incorporation }}</td>
                                                <td>{{ $company->phone }}</td>
                                                <td>{{ $company->region ? ucfirst(strtolower($company->region->name)) : '' }}
                                                </td>
                                                <td>
                                                    @if ($company->approved == 0)
                                                        <span class="badge badge-soft-warning">Pending Review</span>
                                                    @elseif($company->approved == 1)
                                                        <span class="badge badge-soft-success">Approved</span>
                                                    @elseif($company->approved == 99)
                                                        <span class="badge badge-soft-danger">Failed</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown sp-row-actions">
                                                        <button class="btn dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false"
                                                            title="Provider Actions">
                                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            @if ($company->complete == 1)
                                                                <a href="{{ route('companies.details', $company->uuid) }}"
                                                                    class="dropdown-item">
                                                                    View Details
                                                                </a>
                                                            @elseif($company->complete == 0)
                                                                <a href="{{ route('companies.registration', $company->uuid) }}"
                                                                    class="dropdown-item">
                                                                    Continue Registration
                                                                </a>
                                                            @endif

                                                            <a href="{{ route('companies.edit', $company->uuid) }}"
                                                                class="dropdown-item">
                                                                Edit
                                                            </a>

                                                            @if ($company->approved == 0 || $company->approved == 99)
                                                                <a href="{{ route('companies.delete', $company->uuid) }}"
                                                                    class="dropdown-item text-danger delete">
                                                                    Delete
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $serial++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="d-flex justify-content-end">
                                    @if (method_exists($companies, 'links'))
                                        {{ $companies->links() }}
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger"> No any service provider registered</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
@endsection
