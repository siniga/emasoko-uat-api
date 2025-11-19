<div class="modal fade" id="existingDebitOrdersModal" tabindex="-1" aria-labelledby="existingDebitOrdersLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-uppercase font-weight-600 text-white">Existing Debit Orders Found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    We have detected <strong>{{ count($arr_debit_orders) }}</strong> existing debit
                    order(s) linked to this customer account.
                </p>

                <p class="mb-2">
                    <strong>Advice:</strong>
                </p>
                <ul>
                    <li>
                        <strong>Cancel Request</strong> if you first want to review or update existing debit
                        orders to low success rate.
                    </li>
                    <li>
                        <strong>Proceed Anyway</strong> if you are sure you want to create a new payment mandate
                        in addition to the existing one(s).
                    </li>
                </ul>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="12%">Start Date</th>
                                <th width="12%">End Date</th>
                                <th width="15%">Total Amt</th>
                                <th width="15%">Installment Amt</th>
                                <th width="10%">Installments</th>
                                <th width="10%">Paid</th>
                                <th width="10%">Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($arr_debit_orders as $index => $order)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $order['start_date'] }}</td>
                                    <td>{{ $order['end_date'] }}</td>
                                    <td class="text-end">{{ number_format($order['total_amount'] ?? 0) }}</td>
                                    <td class="text-end">{{ number_format($order['installment_amount'] ?? 0) }}</td>
                                    <td class="text-end">{{ $order['number_of_installments'] }}</td>
                                    <td class="text-end">{{ $order['paid_installments'] }}</td>
                                    <td class="text-end">{{ $order['failed_installments'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" id="btn-cancel-request">
                    Cancel Request
                </button>
                <button type="button" class="btn btn-success" id="btn-proceed-request">
                    Proceed Anyway
                </button>
            </div>
        </div>
    </div>
</div>
