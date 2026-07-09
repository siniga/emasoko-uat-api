@extends('layouts.master')

@section('title')
    SMS Templates
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $title ?? 'SMS Templates' }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">SMS Templates</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div x-data="initData()">
        <div class="row">
            <div class="col-lg-8 col-md-8 col-sm-8 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                @include('messages.alerts')
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered dt-responsive nowrap w-100" id="dynamicTable">
                                <thead>
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="25%">Name</th>
                                        <th width="18%">Code</th>
                                        <th>Message</th>
                                        <th width="10%">Status</th>
                                        <th width="12%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($templates as $template)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                {{ $template->name }}<br />
                                                <small class="text-muted">{{ optional($template->category)->name ?? 'Uncategorised' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-primary text-primary">{{ $template->code ?? 'manual' }}</span><br />
                                                <small class="text-muted">{{ $template->channel ?? 'ALL' }}</small>
                                            </td>
                                            <td>{{ $template->message }}</td>
                                            <td>
                                                @if ($template->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="justify-content-between">
                                                <a x-on:click="updateData({{ $template->id }})" class="btn btn-xss">
                                                    <i class="bx bx-edit"></i>
                                                </a>

                                                <a href="{{ route('sms-templates.delete', $template->id) }}"
                                                    class="btn btn-xss delete">
                                                    <i class="bx bx-trash text-danger"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No records were found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end">
                                @if (method_exists($templates, 'links'))
                                    {{ $templates->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-4 col-sm-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="message_wrp"></div>
                            </div>
                        </div>

                        <form @submit.prevent="createOrUpdateForm();" autocomplete="off" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" x-ref="csrf">

                            <div class="row ">
                                <div class="col-lg-12 col-md-12">
                                    <h6 class="text-uppercase text-sm font-weight-600" x-text="page_title"></h6>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                    <div class="mb-3">
                                        <label>Category <span style="color:red">*</span></label>
                                        <select name="category_id" id="category_id" class="form-control" x-model="template_form.category_id">
                                            <option value="">-- Select --</option>
                                            @foreach ($categories as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                    <div class="mb-3">
                                        <label>Name<span style="color:red">*</span></label>
                                        <input name="name" placeholder="Write name..." class="form-control" x-model="template_form.name" required />
                                    </div>
                                </div>

                                <div class="col-lg-8 col-md-8 col-sm-8 col-12">
                                    <div class="mb-3">
                                        <label>Code</label>
                                        <input name="code" placeholder="deduction_success" class="form-control" x-model="template_form.code" />
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label>Channel</label>
                                        <select name="channel" class="form-control" x-model="template_form.channel">
                                            <option value="ALL">ALL</option>
                                            <option value="NMB">NMB</option>
                                            <option value="CRDB">CRDB</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" x-model="template_form.is_active">
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                    <div class="mb-3">
                                        <label>Message<span style="color:red">*</span></label>
                                        <textarea name="message" placeholder="Write message..." class="form-control" x-model="template_form.message" rows="8" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3 text-sm-end d-none d-sm-block">
                                        <button type="submit" name="submit" class="btn btn-primary btn-block"
                                            x-html="btnSubmit" :disabled="formLoading"></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @include('messaging.sms_templates.alpinejs')
@endsection
