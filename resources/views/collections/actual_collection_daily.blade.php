@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $filterRoute = 'collections.actual-daily';
    $detailRoute = 'collections.actual-daily-detail';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
