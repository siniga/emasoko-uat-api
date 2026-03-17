@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $backRoute = 'collections.expected-monthly';
    $exportRoute = 'collections.export-expected-monthly';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
