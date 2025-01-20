@extends('layouts.master')

<style>
    .table th {
        background-color: #E1F5FE !important;
    }
</style>

@section('title')
    Summary of Collection Batch
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Summary of Collection Batch</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('collections.batches') }}">Collection Batches</a></li>
                        <li class="breadcrumb-item active">Summary</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div x-data="initData()">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        <div id="message_wrp" class=""></div>

                        <div class="d-flex justify-content-between mb-2">
                            <div></div>

                            <div class="text-sm-end d-none d-sm-block">
                                @if (Auth::user()->hasRole(['admin']))
                                <button x-html="btnStatus" title="Collection Status" class="btn btn-outline-success btn-sm"
                                    x-on:click="fetchCollectionStatus();"></button>
                                @endif    
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <table class="table table-stripped table-bordered">
                                    <tr>
                                        <td colspan="4" class="font-weight-600 text-uppercase">
                                            Batch Information
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Service Provider</th>
                                        <td>
                                            <span>{{ $batch->partner->business_name }}</span>
                                        </td>

                                        <th>Corporate Reference</th>
                                        <td>
                                            <span>{{ $batch->corporateReference }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th width=25%>Collection Reference</th>
                                        <td width=25%>
                                            <span>{{ $batch->collectionReference }}</span>
                                        </td>

                                        <th>Processed</th>
                                        <td>
                                            @if($batch->processed == 0)
                                                <span class="badge bg-danger">No</span>
                                            @elseif($batch->processed == 1)
                                                <span class="badge bg-success-1">Yes</span>
                                            @endif
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Remarks</th>
                                        <td colspan="3">
                                            <span>{{ $batch->remarks }}</span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Created Date</th>
                                        <td>
                                            <span>{{ date('d-m-Y', strtotime($batch->created_at)) }}</span>
                                        </td>

                                        <th>Updated Date</th>
                                        <td>
                                            <span>{{ date('d-m-Y', strtotime($batch->updated_at)) }}</span>
                                        </td>
                                    </tr>


                                </table>
                            </div>
                        </div>

                        @if($batch->pushed == 0)

                        @endif

                        @if($batch->processed == 1)
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-responsive">
                                        <table class="table table-nowrap align-middle table-bordered">
                                            <thead>
                                                <tr>
                                                    <td colspan="4" class="font-weight-600 text-uppercase">
                                                        Deduction History
                                                    </td>
                                                </tr>
                                            </thead>

                                            <tbody>
                                            <tr>
                                                    <th width="3%">#</th>
                                                    <th width="8%">Mandate Ref</th>
                                                    <th width="12%">Account Name</th>
                                                    <th width="10%">Account No.</th>
                                                    <th width="8%">Expected Amt</th>
                                                    <th width="8%">Paid Amt</th>
                                                    <th width="8%">Current Balance</th>
                                                    <th width="8%">Outstanding Amt</th>
                                                    <th width="8%">Status</th>
                                                    <th width="24%">Message</th>
                                                </tr>

                                                @php
                                                    $sum_expected_amount = 0;
                                                    $sum_paid_amount = 0;
                                                    $sum_current_balance = 0;
                                                    $sum_outstanding_amount = 0;
                                                @endphp

                                                @foreach ($arr_data as $val)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $val['reference'] }}</td>
                                                        <td>{{ $val['account_name'] }}</td>
                                                        <td>{{ $val['account_number'] }}</td>
                                                        <td class="text-sm-end">{{ number_format($val['expected_amount']) }}</td>
                                                        <td class="text-sm-end">{{ number_format($val['paid_amount']) }}</td>
                                                        <td class="text-sm-end">{{ number_format($val['current_balance']) }}</td>
                                                        <td class="text-sm-end">{{ number_format($val['outstanding_amount']) }}</td>
                                                        <td>
                                                            @if($val['payment_status'] == "SUCCESS")
                                                                <span class="badge bg-success-1">Paid</span>
                                                            @elseif($val['payment_status'] == "FAIL")  
                                                                <span class="badge bg-danger">Unpaid</span>    
                                                            @else
                                                                <span class="badge bg-warning">Processing</span>  
                                                            @endif
                                                        </td>
                                                        <td>{{ $val['message'] }}</td>
                                                    </tr>

                                                    @php
                                                        $sum_expected_amount += $val['expected_amount'];
                                                        $sum_paid_amount += $val['paid_amount'];
                                                        $sum_current_balance += $val['current_balance'];
                                                        $sum_outstanding_amount += $val['outstanding_amount'];
                                                    @endphp
                                                @endforeach
                                                <tr>
                                                    <td colspan="4" class="font-weight-600">Total</td>
                                                    <td class="text-sm-end">{{ number_format($sum_expected_amount) }}</td>
                                                    <td class="text-sm-end">{{ number_format($sum_paid_amount) }}</td>
                                                    <td class="text-sm-end">{{ number_format($sum_current_balance) }}</td>
                                                    <td class="text-sm-end">{{ number_format($sum_outstanding_amount) }}</td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- include modals -->
        {{-- @include('payment_mandate.modals.verify_otp_modal')
        @include('payment_mandate.modals.cancellation_modal') --}}
    </div>
@endsection


@section('script')
    <script type="text/javascript">
        //initiate data
        function initData() {
            return {
                sidebarOpen: false,
                eModal: "Collection_Information",
                failed: "{{ $batch->failed }}",

                formData: {
                    batch_id: "{{ $batch->id }}",
                },
               
                formLoading: false,
                formMessage: "",
                btnStatus: '<i class="fa fa-check"></i> Collection Status',

                //fetch collection status
                fetchCollectionStatus() {
                    this.formLoading = true;
                    this.btnStatus = '<i class="fas fa-spinner fa-spin"></i> Fetch collection status....';

                    fetch("/collections/fetch-status", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(this.formData),
                        }).then(response => response.json())
                        .then(responseJson => {
                            $('#message_wrp').html(
                                "<div class='alert alert-success rounded-sm'>Collection status are processed, Wait for 2-5 minutes and refresh the page.</div>"
                            );
                        }).catch(() => {
                            //display message
                            $('#message_wrp').html(
                                "<div class='alert alert-danger rounded-sm'>Something went wrong.</div>"
                            );

                        }).finally(() => {
                            this.formLoading = false;
                            this.btnStatus = '<i class="fa fa-check"></i> Collection Status';
                        });
                },

            }
        }
    </script>
@endsection
