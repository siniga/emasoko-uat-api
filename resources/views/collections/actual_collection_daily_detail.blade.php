@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $backRoute = 'collections.actual-daily';
    $exportRoute = 'collections.export-actual-daily';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
