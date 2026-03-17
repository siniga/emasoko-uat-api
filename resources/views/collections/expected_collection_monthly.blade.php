@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $filterRoute = 'collections.expected-monthly';
    $detailRoute = 'collections.expected-monthly-detail';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
