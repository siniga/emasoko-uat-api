@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $backRoute = 'collections.expected-daily';
    $exportRoute = 'collections.export-expected-daily';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
