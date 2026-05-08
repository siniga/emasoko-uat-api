<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
        @if (session('success'))
            <div class="alert alert-success">
                <span class="text-medium font-weight-500">{{ session()->get('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                <span class="text-medium font-weight-500">{{ session()->get('error') }}</span>
            </div>
        @endif

        @if (session()->get('danger'))
            <div class="alert alert-danger">
                <span class="text-medium font-weight-500">{{ session()->get('danger') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
