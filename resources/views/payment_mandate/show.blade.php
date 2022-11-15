@extends('layouts.master')

@section('title')
    Summary of Payment Mandate
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Dashboard
        @endslot
        @slot('title')
            Summary of Payment Mandate
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @elseif(session()->get('danger'))
                        <div class="alert alert-danger">
                            {{ session()->get('danger') }}
                        </div><br />
                    @endif

                    <div class="d-flex justify-content-between mb-2">
                        <div>

                        </div>

                        <div class="text-sm-end d-none d-sm-block">
                            @if ($debit_order->status == 'saved')
                                <a href="{{ route('payment-mandate.preview', [$debit_order->customer_account_id, $debit_order->id]) }}"
                                    title="Confirm Application" class="btn btn-outline-success text-medium btn-sm">
                                    <i class='bx bxs-check-square font-size-12'></i> Confirm Application
                                </a>
                            @endif

                            {{-- <a href="#" title="Send Remainder" class="btn btn-outline-primary text-medium btn-sm">
                                <i class="bx bxs-chat font-size-12"></i> Send Remainder
                            </a> --}}

                            @if ($debit_order->approved == 0)
                                <a href="{{ route('payment-mandate.edit', $debit_order->id) }}" title="Edit"
                                    class="btn btn-outline-secondary text-medium btn-sm">
                                    <i class="bx bx-edit font-size-12"></i> Edit
                                </a>

                                <a href="{{ route('payment-mandate.delete', $debit_order->id) }}" title="Delete"
                                    class="btn btn-outline-danger text-medium btn-sm delete">
                                    <i class="bx bx-trash font-size-12"></i> Delete
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <table class="table table-stripped table-bordered">
                                <tr>
                                    <th colspan="2" class="font-weight-600">
                                        Deduction Channel
                                    </th>
                                </tr>

                                <tr>
                                    <td width=25%>Payment Channel</td>
                                    <td width=75%>
                                        <span>{{ $debit_order->customerAccount->accountType->name ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>

                                    <td>Bank/MNO Name</td>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->account->name ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Name of Account Holder</td>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->account_name ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>

                                    <td>Account Number</td>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->account_number ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Phone <small>(Connected to Bank)</small></td>
                                    <td>
                                        <span>{{ $debit_order->customerAccount->phone ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Status</td>
                                    <td>
                                        <span>
                                            @if ($debit_order->customerAccount->status == 'partial')
                                                <span class="badge bg-info">Partial Approved</span>
                                            @elseif($debit_order->customerAccount->status == 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($debit_order->customerAccount->status == 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>

                                @if ($debit_order->customerAccount->remarks)
                                    <tr>
                                        <td>Remarks</td>
                                        <td>
                                            <span>{{ $debit_order->customerAccount->remarks ?? '' }}</span>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th colspan="2" class="font-weight-600">
                                        Customer Deduction Information
                                    </th>
                                </tr>

                                <tr>
                                    <td width=25%>Loan Amount</td>
                                    <td width=75%>
                                        <span>{{ number_format($debit_order->total_amount) ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Debit Type</td>
                                    <td>
                                        <span>{{ $debit_order->debit_type ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Installment Amount</td>
                                    <td>
                                        <span>
                                            @if ($debit_order->debit_type == 'FIXED')
                                                {{ number_format($debit_order->installment_amount) ?? '' }}
                                            @elseif($debit_order->debit_type == 'DYNAMIC')
                                                Min:
                                                {{ number_format($debit_order->min_installment_amount) ?? '' }}<br />
                                                Max:
                                                {{ number_format($debit_order->max_installment_amount) ?? '' }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Peridiocity</td>
                                    <td>
                                        <span>{{ $debit_order->periodicity }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Number of Deduction</td>
                                    <td>
                                        <span>{{ $debit_order->installment_number ?? '' }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Deduction Start Date</td>
                                    <td>
                                        <span>{{ date('d-m-Y', strtotime($debit_order->start_date)) }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Deduction End Date</td>
                                    <td>
                                        <span>{{ date('d-m-Y', strtotime($debit_order->end_date)) }}</span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Evidence of Aggrement</td>
                                    <td>
                                        <span>
                                            @if ($debit_order->evidence_of_aggrement != null)
                                                <a download=""
                                                    href="{{ asset('assets/uploads/docs/' . $debit_order->evidence_of_aggrement) }}">
                                                    {{ $debit_order->evidence_of_aggrement }}
                                                </a>
                                            @else
                                                NIL
                                            @endif
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Consent Status</td>
                                    <td>
                                        @if ($debit_order->approved == 0)
                                            <span class="badge badge-pill badge-soft-danger">Pending</span>
                                        @elseif($debit_order->approved == 1)
                                            <span class="badge badge-pill badge-soft-success">Approved</span>
                                        @elseif($debit_order->approved == 99)
                                            <span class="badge badge-pill badge-soft-danger">Rejected</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--./col -->
                    </div>
                    <!--./row -->
                </div>
                <!--./card-body -->
            </div>
            <!--./card-->
        </div>
        <!--./col -->
    </div>
    <!-- end row -->

    <div id="offcanvas-wrp"></div>
    <!--./offcanvas -->
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //close offcanvas wrp
            $('#offcanvas-wrp').hide();

            //close canvas
            $(document).on('click', '#close-offcanvas', function(event) {
                $('#offcanvas-wrp').animate({
                    width: "hide"
                });
                //reload page
                location.reload();
            });

            //update payment mandate
            $(document).on('click', '.update-mandate', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //debit-order-id
                var debitOrderId = $(this).attr('debit-order-id');

                //ajax request
                // var jqXHR = $.ajax({
                //     type: "GET",
                //     url: "/companies/" + companyId + "/assessment/?page=companyInformation",
                // }).done(function(data) {
                //     $('#offcanvas-wrp').html(data);

                // }).fail(function(jqXHR, textStatus, errorThrown) {
                //     alert('Failed to update progress');
                //     if (console && console.log) {
                //         console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                //     }
                // });
            });

            //send remainder
            $(document).on('click', '.send-remainder', function(event) {
                event.preventDefault();

                $('#offcanvas-wrp').animate({
                    width: "show"
                });

                //debit-order-id
                var debitOrderId = $(this).attr('debit-order-id');

                //ajax request
                var jqXHR = $.ajax({
                    type: "GET",
                    url: "/payment-mandate/send-remainder/" + debitOrderId,
                }).done(function(data) {
                    $('#offcanvas-wrp').html(data);

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    alert('Failed to update progress');
                    if (console && console.log) {
                        console.log("Loading Ajax: " + textStatus + ", " + errorThrown);
                    }
                });
            });
        });
    </script>
@endsection
