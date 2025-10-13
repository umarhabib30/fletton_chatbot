@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="section-block" id="basicform">
                <h3 class="section-title">Edit Template</h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('admin/template/update') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" value="{{ $template->id }}" id="">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="col-form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="{{ $template->title }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label class="col-form-label">Template ID</label>
                                <input type="text" class="form-control" name="template_id" value="{{ $template->template_id }}">
                            </div>

                            <div class="form-group col-md-6">
                                <label class="col-form-label">Is Default</label>
                                <select name="is_default" class="form-control">
                                    <option value="0" {{ $template->is_default == 0 ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ $template->is_default == 1 ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label class="col-form-label">Is Used For</label>
                                <select name="used_for" class="form-control">
                                    <option value="other" {{ $template->used_for == 'other' ? 'selected' : '' }}>Others</option>
                                    <option value="new_user" {{ $template->used_for == 'new_user' ? 'selected' : '' }}>For New Users</option>
                                    <option value="old_user" {{ $template->used_for == 'old_user' ? 'selected' : '' }}>For Older User</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ url('admin/templates/index') }}" class="btn btn-primary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
