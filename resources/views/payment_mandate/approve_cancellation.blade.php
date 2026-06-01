@extends('layouts.master')

@section('title')
    Approve Mandate Cancellation
@endsection

@section('content')
    @include('payment_mandate.partials.approval_page', [
        'pageTitle' => 'Approve Mandate Cancellation',
        'pageSubtitle' => 'Review pending mandate cancellation requests and approve only the records that are ready to move out of the active collection cycle.',
        'guidanceText' => 'Use this page to review cancellation requests submitted by operations teams. Select only mandates that have been validated and are safe to cancel.',
        'tableTitle' => 'Pending Cancellation Requests',
        'tableHint' => 'Select one or more requests below, then approve them in a single step.',
        'actionRoute' => 'payment-mandate.approve-cancellation',
        'actionName' => 'approve_cancellation',
        'actionValue' => 'approve_cancellation',
        'buttonLabel' => 'Approve Cancellation',
        'buttonIcon' => 'bx-chevrons-right',
        'requestLabel' => 'Cancellation',
        'emptyMessage' => 'No cancellation requests at the moment!',
    ])
@endsection

@section('script')
@endsection
