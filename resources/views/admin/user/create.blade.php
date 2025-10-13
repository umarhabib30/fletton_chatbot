@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="section-block" id="basicform">
                <h3 class="section-title">Create New User</h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('admin/user/store') }}" method="post">
                        @csrf
                        <div class="row">
                            <!-- Email -->
                            <div class="form-group col-md-6">
                                <label for="name" class="col-form-label">Name</label>
                                <input id="name" type="text" class="form-control" name="name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email" class="col-form-label">Email</label>
                                <input id="email" type="email" class="form-control" name="email" required>
                            </div>

                            <!-- Password -->
                            <div class="form-group col-md-6">
                                <label for="password" class="col-form-label">Password</label>
                                <input id="password" type="password" class="form-control" name="password" required>
                            </div>

                            <!-- Role -->
                            <div class="form-group col-md-6">
                                <label for="role" class="col-form-label">Role</label>
                                <select name="role" id="role" class="form-control" required>
                                    {{-- <option value="admin">Admin</option> --}}
                                    <option value="1">Manager</option>
                                    {{-- <option value="user">User</option> --}}
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Save User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
