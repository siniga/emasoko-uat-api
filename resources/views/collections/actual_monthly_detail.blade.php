@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-detail.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $backRoute = 'collections.actual-monthly';
    $exportRoute = 'collections.export-actual-monthly';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
