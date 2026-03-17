@extends('layouts.master')

@section('title')
    {{ $title }}
@endsection

@php
    $filterRoute = 'collections.actual-monthly';
    $detailRoute = 'collections.actual-monthly-detail';
@endphp

@section('content')
    @include('collections.partials.summary_page')
@endsection
