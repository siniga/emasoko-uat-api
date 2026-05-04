@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-summary.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $filterRoute = 'collections.actual-daily';
    $detailRoute = 'collections.actual-daily-detail';
    $summaryExportRoute = 'collections.export-actual-daily-summary';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
