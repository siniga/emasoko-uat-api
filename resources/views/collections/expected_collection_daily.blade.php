@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-summary.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $filterRoute = 'collections.expected-daily';
    $detailRoute = 'collections.expected-daily-detail';
    $summaryExportRoute = 'collections.export-expected-daily-summary';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
