<style>
    .pm-existing-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(21, 53, 103, 0.18);
    }

    .pm-existing-modal__header {
        background: linear-gradient(135deg, #8a2d2d 0%, #c24a4a 100%);
        color: #fff;
        padding: 1rem 1.1rem;
        border-bottom: 0;
    }

    .pm-existing-modal__title {
        margin-bottom: 0.2rem;
        font-size: 1rem;
        font-weight: 700;
         color: #fff;
        text-transform: uppercase;
    }

    .pm-existing-modal__subtitle {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.85rem;
    }

    .pm-existing-modal .modal-body {
        padding: 1.1rem;
        background: #fff;
    }

    .pm-existing-modal__note {
        border: 1px dashed #f0c2c2;
        border-radius: 14px;
        background: #fff8f8;
        color: #8a4b4b;
        padding: 0.9rem 0.95rem;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .pm-existing-modal__table {
        border: 1px solid #f0dada;
        border-radius: 14px;
        overflow: hidden;
    }

    .pm-existing-modal__table thead th {
        background: #fff1f1;
        color: #8a2d2d;
        font-weight: 700;
        border-color: #f1d1d1;
    }

    .pm-existing-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.65rem;
        flex-wrap: wrap;
        padding: 1rem 1.1rem 1.1rem;
        background: #fff;
        border-top: 1px solid #f1dede;
    }
</style>

<div class="modal fade pm-existing-modal" id="existingDebitOrdersModal" tabindex="-1" aria-labelledby="existingDebitOrdersLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pm-existing-modal__header">
                <div>
                    <h5 class="pm-existing-modal__title">Existing Debit Orders Found</h5>
                    <p class="pm-existing-modal__subtitle">This customer account already has active or recent mandate activity that should be reviewed first.</p>
                </div>
            </div>

            <div class="modal-body">
                <div class="pm-existing-modal__note">
                    We detected <strong>{{ count($arr_debit_orders) }}</strong> existing debit order(s) linked to this customer account.
                    Review the current schedule below before deciding whether to create another mandate.
                    <br><br>
                    <strong>Recommendation:</strong> cancel this request if you need to review or clean up the existing orders first. Proceed only when creating an additional mandate is intentional.
                </div>

                <div class="table-responsive pm-existing-modal__table">
                    <table class="table table-sm table-bordered mb-0">
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
                                    <td>{{ date('d-m-Y', strtotime($order['start_date'])) }}</td>
                                    <td>{{ date('d-m-Y', strtotime($order['end_date'])) }}</td>
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

            <div class="pm-existing-modal__actions">
                <button type="button" class="btn btn-outline-danger" x-on:click="cancelExistingMandateRequest()">
                    Cancel Request
                </button>
                <button type="button" class="btn btn-success" x-on:click="proceedWithMandate()">
                    Proceed Anyway
                </button>
            </div>
        </div>
    </div>
</div>
