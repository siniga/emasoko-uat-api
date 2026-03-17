@extends('layouts.master')

@section('title')
    Approve Mandate Deletion
@endsection

@section('content')
    @include('payment_mandate.partials.approval_page', [
        'pageTitle' => 'Approve Mandate Deletion',
        'pageSubtitle' => 'Review deletion requests carefully before approval. This step should be reserved for mandates that should be removed from the operational dataset.',
        'guidanceText' => 'Deletion is a stronger action than cancellation or closing. Confirm the request is valid and that the mandate should no longer remain in active operational use.',
        'tableTitle' => 'Pending Deletion Requests',
        'tableHint' => 'Select one or more requests below, then approve them in a single step.',
        'actionRoute' => 'payment-mandate.approve-deletion',
        'actionName' => 'approve_deletion',
        'actionValue' => 'approve_deletion',
        'buttonLabel' => 'Approve Deletion',
        'buttonIcon' => 'bx-trash',
        'requestLabel' => 'Deletion',
        'emptyMessage' => 'No deletion requests at the moment!',
    ])
@endsection

@section('script')
@endsection
