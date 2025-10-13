@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="section-block" id="basicform">
                <h3 class="section-title">Create New Template</h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('admin/template/store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="inputText3" class="col-form-label">Title</label>
                                <input id="inputText3" type="text" class="form-control" name="title">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="inputText3" class="col-form-label">Template ID</label>
                                <input id="inputText3" type="text" class="form-control" name="template_id">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="inputText3" class="col-form-label">Is Default</label>
                                <select name="is_default" id="" class="form-control">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="inputText3" class="col-form-label">Is Used For</label>
                                <select name="used_for" id="" class="form-control">
                                    <option value="other">Others</option>
                                    <option value="new_user">For New Users</option>
                                    <option value="old_user">For Older User</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
