@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-summary.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $filterRoute = 'collections.expected-monthly';
    $detailRoute = 'collections.expected-monthly-detail';
    $summaryExportRoute = 'collections.export-expected-monthly-summary';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
