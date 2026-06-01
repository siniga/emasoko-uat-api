@extends('layouts.master')

@section('title')
    Approve Mandate Archive
@endsection

@section('content')
    @include('payment_mandate.partials.approval_page', [
        'pageTitle' => 'Approve Mandate Archive',
        'pageSubtitle' => 'Archive requests help move completed or inactive mandates out of the day-to-day portfolio while preserving their historical record.',
        'guidanceText' => 'Use archive approval for mandates that no longer need active operational attention but should remain available for reporting and audit history.',
        'tableTitle' => 'Pending Archive Requests',
        'tableHint' => 'Select one or more requests below, then approve them in a single step.',
        'actionRoute' => 'payment-mandate.approve-archive',
        'actionName' => 'approve_request',
        'actionValue' => 'approve_request',
        'buttonLabel' => 'Approve Archive',
        'buttonIcon' => 'bx-archive-in',
        'requestLabel' => 'Archive',
        'emptyMessage' => 'No archive requests at the moment!',
    ])
@endsection

@section('script')
@endsection
