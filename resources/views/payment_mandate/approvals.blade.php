@extends('layouts.master')

@section('title')
    Mandate Approvals
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/payment-mandate-approvals.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @include('payment_mandate.partials.approval_page', [
        'pageTitle' => $pageTitle,
        'pageSubtitle' => $pageSubtitle,
        'guidanceText' => $guidanceText,
        'tableTitle' => $tableTitle,
        'tableHint' => $tableHint,
        'actionRoute' => $actionRoute,
        'actionName' => $actionName,
        'actionValue' => $actionValue,
        'buttonLabel' => $buttonLabel,
        'buttonIcon' => $buttonIcon,
        'requestLabel' => $requestLabel,
        'emptyMessage' => $emptyMessage,
        'dd_requests' => $dd_requests,
        'approvalTabs' => $approvalTabs,
        'activeTab' => $activeTab,
    ])
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const approvalForm = document.querySelector('.js-approval-form');

            if (!approvalForm) {
                return;
            }

            const approvalButton = approvalForm.querySelector('.js-approval-submit');

            approvalForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const selectedRows = approvalForm.querySelectorAll('input[name="request_id[]"]:checked');
                const requestLabel = approvalButton?.dataset.requestLabel || 'Approval';
                const buttonLabel = approvalButton?.dataset.buttonLabel || 'Approve';
                const requestCount = selectedRows.length;

                if (requestCount === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No request selected',
                        text: `Select at least one ${requestLabel.toLowerCase()} request before continuing.`,
                        confirmButtonColor: '#153567',
                    });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: `Confirm ${requestLabel}`,
                    text: `You are about to ${buttonLabel.toLowerCase()} for ${requestCount} selected request(s).`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, continue',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#153567',
                    cancelButtonColor: '#d33',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        approvalForm.submit();
                    }
                });
            });
        });
    </script>
@endsection
