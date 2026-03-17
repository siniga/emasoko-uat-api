@extends('layouts.master')

@section('title')
    Edit Customer Account
@endsection

<style>
    .cae-shell {
        --cae-primary: #153567;
        --cae-border: #dce6f5;
        --cae-muted: #6b7c93;
    }

    .cae-hero {
        border: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #153567 0%, #1f4b8b 100%);
        box-shadow: 0 18px 40px rgba(21, 53, 103, 0.18);
        overflow: hidden;
    }

    .cae-hero__body {
        padding: 1.5rem;
    }

    .cae-kicker {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cae-title {
        color: #fff;
        font-size: 1.65rem;
        font-weight: 700;
        margin: 0.9rem 0 0.45rem;
    }

    .cae-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0;
        max-width: 760px;
    }

    .cae-card {
        border: 1px solid var(--cae-border);
        border-radius: 18px;
        box-shadow: 0 16px 40px rgba(29, 53, 87, 0.08);
    }

    .cae-section {
        border: 1px solid #e7edf6;
        border-radius: 16px;
        background: #fbfcfe;
        padding: 1.2rem;
        margin-bottom: 1.25rem;
    }

    .cae-section__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--cae-primary);
        margin-bottom: 0.2rem;
    }

    .cae-section__hint {
        color: var(--cae-muted);
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .cae-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .cae-summary__pill {
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 0.84rem;
    }

    .cae-actionbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #edf2f8;
    }

    .cae-actionbar__meta {
        color: var(--cae-muted);
        font-size: 0.9rem;
    }

    @media (max-width: 767.98px) {
        .cae-actionbar {
            flex-direction: column;
            align-items: stretch;
        }

        .cae-actionbar .btn {
            width: 100%;
        }
    }
</style>

@section('content')
    <div class="cae-shell">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Edit Customer Accounts</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer-accounts.lists') }}">Customer Accounts</a>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <div class="card cae-hero mb-4">
        <div class="cae-hero__body">
            <span class="cae-kicker">Customer Account Maintenance</span>
            <h2 class="cae-title">Edit {{ $customer_account->account_name ?? $customer_account->phone }}</h2>
            <p class="cae-subtitle">Update the customer account profile, channel details, and customer contact information while keeping mandate creation aligned to the correct settlement details.</p>
            <div class="cae-summary">
                <span class="cae-summary__pill"><strong>Channel:</strong> {{ $customer_account->accountType->name ?? '-' }}</span>
                <span class="cae-summary__pill"><strong>Provider:</strong> {{ $customer_account->account->name ?? '-' }}</span>
                <span class="cae-summary__pill"><strong>Status:</strong> {{ ucfirst($customer_account->status ?? 'draft') }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card cae-card">
                <div class="card-body">

                    <!--./row -->
                    <div class="row">
                        <div class="col-lg-12">
                            {{ Form::open(['url' => route('customer-accounts.update', $customer_account->id), 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) }}
                            @method('PUT')
                            {{ Form::token() }}

                            <div class="cae-section">
                                <div class="cae-section__title">Account Channel Details</div>
                                <div class="cae-section__hint">Choose the payment channel and update the linked bank or MNO account information used for this customer.</div>

                                <div class="row ">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Payment Channel <span class="text-danger">*</span></label>
                                            @php
                                                $account_type_options = [];
                                                $options = [];
                                            @endphp
                                            @foreach ($account_types as $val)
                                                @php
                                                    $options[$val->id] = $val->name;
                                                @endphp
                                            @endforeach
                                            @php
                                                $account_type_options = $options;
                                                $options = ['' => 'Select'] + $options;
                                            @endphp

                                            {{ Form::select('account_type_id', $options, old('account_type_id', $customer_account->account_type_id ?? ''), ['class' => 'form-control account_type_id', 'id' => 'account_type_id']) }}
                                            <div class="text-danger">{{ $errors->first('account_type_id') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="bank_mno_name">Bank Name/MNO <span class="text-danger">*</span></label>
                                            @php $options = []; @endphp
                                            @foreach ($accounts as $val)
                                                @php
                                                    $options[$val->id] = $val->name;
                                                @endphp
                                            @endforeach
                                            @php
                                                $options = ['' => 'Select'] + $options;
                                            @endphp

                                            {{ Form::select('account_id', $options, old('account_id', $customer_account->account_id ?? ''), ['class' => 'form-control account_id', 'id' => 'account_id']) }}
                                            <div class="text-danger">{{ $errors->first('account_id') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4" id="account_name" style="display: block;">
                                        <div class="mb-3">
                                            <label>Name of Account Holder <span class="text-danger">*</span></label>
                                            {{ Form::text('account_name', old('account_name', $customer_account->account_name ?? ''), ['class="form-control"', 'placeholder="Enter name of account holder..."', 'id="account_name"']) }}
                                            <div class="text-danger">{{ $errors->first('account_name') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4" id="account_number" style="display: block;">
                                        <div class="mb-3">
                                            <label>Bank Account Number <span class="text-danger">*</span></label>
                                            {{ Form::text('account_number', old('account_number', $customer_account->account_number ?? ''), ['class="form-control"', 'placeholder="Enter account number..."']) }}
                                            <div class="text-danger">{{ $errors->first('account_number') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4" id="account_number_phone" style="display: none;">
                                        <div class="mb-3">
                                            <label>MNO Phone <span class="text-danger">*</span></label>
                                            {{ Form::text('account_number_phone', old('account_number_phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone number..."']) }}
                                            <div class="text-danger">{{ $errors->first('account_number_phone') }}</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4" id="account_phone" style="display: block;">
                                        <div class="mb-3">
                                            <label>Phone <span class="text-danger">*</span></label>
                                            {{ Form::text('phone', old('phone', $customer_account ? reverse_casting_phone($customer_account->phone) : ''), ['class="form-control"', 'placeholder="Enter phone connected to the bank account..."']) }}
                                            <div class="text-danger">{{ $errors->first('phone') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="cae-section">
                                <div class="cae-section__title">Customer Details</div>
                                <div class="cae-section__hint">Keep customer identity and contact information synchronized with the account profile.</div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>NIDA Number</label>
                                            {{ Form::text('nida_number', old('nida_number', $customer_account->customer->id_number ?? ''), ['class="form-control input-mask"', 'placeholder="Enter NIDA number..."', 'data-inputmask' => "'mask': '99999999-99999-99999-99'"]) }}
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Email</label>
                                            {{ Form::email('email', old('email', $customer_account->customer->email ?? ''), ['class="form-control input-mask"', 'placeholder="Enter email..."']) }}
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label>Address</label>
                                            {{ Form::text('address', old('address', $customer_account->customer->address ?? ''), ['class="form-control input-mask"', 'placeholder="Enter address..."']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="cae-actionbar">
                                <div class="cae-actionbar__meta">Save only after confirming the correct channel, provider, and customer identifiers. Approved accounts remain locked from edit.</div>
                                <div class="d-flex gap-2">
                                        <a href="{{ route('customer-accounts.lists') }}"
                                            class="btn btn-outline-danger btn-xs text-medium">
                                            Cancel
                                        </a>

                                        <button type="submit" class="btn btn-primary">
                                            Save Changes
                                        </button>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- end row -->
@endsection
@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            //before on change
            var accountTypeId = $(".account_type_id").val();

            if (accountTypeId == 1 || accountTypeId == 2) {
                //change bank or mno name
                if (accountTypeId == 1) {
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', '')
                    $('#account_number').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#account_number_phone').css('display', '')
                    $('#account_phone').css('display', 'none')
                }
            }

            //on select account account type id
            $('.account_type_id').on('change', function(e) {
                e.preventDefault();
                accountTypeId = $(this).val();

                //change bank or mno name
                if (accountTypeId == 1) {
                    $('.bank_mno_name').html('Bank Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', '')
                    $('#account_number').css('display', '')
                    $('#account_number_phone').css('display', 'none')
                    $('#account_phone').css('display', '')
                } else if (accountTypeId == 2) {
                    $('.bank_mno_name').html('MNO Name <span class="text-danger">*</span>');

                    //hide or show
                    $('#account_name').css('display', 'none')
                    $('#account_number').css('display', 'none')
                    $('#account_number_phone').css('display', '')
                    $('#account_phone').css('display', 'none')
                }

                //query for data
                $.ajax({
                    url: '/account-types/' + accountTypeId,
                    type: "get",
                    success: function(data) {
                        $('.account_id').html(data);
                    }
                });
            });
        });
    </script>
@endsection
