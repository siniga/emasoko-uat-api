@extends('layouts.master')

@section('title')
    Approve Mandate Closing
@endsection

@section('content')
    @include('payment_mandate.partials.approval_page', [
        'pageTitle' => 'Approve Mandate Closing',
        'pageSubtitle' => 'Review closing requests for mandates that have reached the end of their operational lifecycle and should be marked as closed.',
        'guidanceText' => 'Approve closing only when the mandate has completed its intended lifecycle and there are no remaining operational actions expected on it.',
        'tableTitle' => 'Pending Closing Requests',
        'tableHint' => 'Select one or more requests below, then approve them in a single step.',
        'actionRoute' => 'payment-mandate.approve-closing',
        'actionName' => 'approve_closing',
        'actionValue' => 'approve_closing',
        'buttonLabel' => 'Approve Closing',
        'buttonIcon' => 'bx-window-close',
        'requestLabel' => 'Closing',
        'emptyMessage' => 'No closing requests at the moment!',
    ])
@endsection

@section('script')
@endsection
