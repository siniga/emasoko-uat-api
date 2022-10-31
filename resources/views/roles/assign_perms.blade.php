@extends('layouts.master')

@section('title')
    Roles
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Roles
        @endslot
        @slot('title')
            Roles
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">

                    @if (session()->get('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div><br />
                    @endif

                    {{ Form::open(['url' => route('roles.store-perms', $role->id), 'method' => 'POST', 'class' => 'form-horizontal']) }}
                    {{ Form::token() }}

                    @foreach ($perms as $key => $value)
                        <div class="mt-2"></div>
                        <h6 class="font-weight-600"> {{ $key }}</h6>
                        <input type="hidden" name="classes[]" value="{{ $key }}">
                        <input type="hidden" name="role_id" value="{{ $role->id }}">

                        <div class="row">
                            <div class="col-md-12">
                                <table>
                                    <tr>
                                        <?php $serial = 0; ?>
                                        @foreach ($value as $k => $v)
                                            @if ($serial % 5 == 0)
                                    </tr>
                                    <tr>
                    @endif
                    <td>
                        <input type="checkbox" name="perms[]" value="{{ $v[1] }}"
                            {{ in_array($v[1], $assigned_perms) ? 'checked' : '' }} />
                        <label>{{ $v[2] }} &nbsp;&nbsp;&nbsp;</label>&nbsp;
                    </td>
                    <?php $serial++; ?>
                    @endforeach
                    </tr>
                    </table>
                </div><!-- ./col-sm-12 -->
            </div><!-- ./row -->
            @endforeach

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3 text-sm-end d-none d-sm-block">
                        <a href="{{ route('roles.index') }}" class="btn btn-danger btn-xs text-medium">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-xs text-medium">Assign</button>
                    </div>
                </div>
                <!--./col-lg-12 -->
            </div>
            <!--./row -->
            {{ Form::close() }}


        </div>
    </div>
    </div>
    </div>
    <!-- end row -->
@endsection
