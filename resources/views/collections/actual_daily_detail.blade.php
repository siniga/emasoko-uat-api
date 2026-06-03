@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/css/collection-detail.css') }}" rel="stylesheet" type="text/css" />
@endsection

@php
    $backRoute = 'collections.actual-daily';
    $exportRoute = 'collections.export-actual-daily';
@endphp

@section('content')
    @include('collections.partials.detail_page')
@endsection
