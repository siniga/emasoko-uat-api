@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-detail.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $backRoute = 'collections.expected-daily';
    $exportRoute = 'collections.export-expected-daily';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
