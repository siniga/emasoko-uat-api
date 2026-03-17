@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $filterRoute = 'collections.expected-daily';
    $detailRoute = 'collections.expected-daily-detail';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
