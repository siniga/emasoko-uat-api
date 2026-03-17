@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $backRoute = 'collections.actual-monthly';
    $exportRoute = 'collections.export-actual-monthly';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
