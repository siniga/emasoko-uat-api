<!-- JAVASCRIPT -->
<script src="{{ URL::asset('assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/metismenu/metismenu.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/node-waves/node-waves.min.js') }}"></script>

{{-- <!-- jquery step -->
<script src="{{ URL::asset('/assets/libs/jquery-steps/jquery-steps.min.js') }}"></script>
 --}}

<!--jquery validation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
{{-- <script src="http://jqueryvalidation.org/files/dist/additional-methods.min.js"></script> --}}

<!-- form mask -->
<script src="{{ URL::asset('assets/libs/inputmask/inputmask.min.js') }}"></script>

<!-- form mask init -->
<script src="{{ URL::asset('assets/js/pages/form-mask.init.js') }}"></script>

@yield('script')

<!-- App js -->
<script src="{{ URL::asset('assets/js/app.min.js') }}"></script>

<!-- A js -->
<script src="{{ URL::asset('assets/js/easy-number-separator.js') }}"></script>

<!-- notify js -->
<script src="{{ URL::asset('assets/js/notify.js') }}"></script>

@yield('script-bottom')
