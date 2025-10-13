@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="section-block" id="basicform">
                <h3 class="section-title">Edit User</h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('admin/user/update') }}" method="post">
                        @csrf
                      <input type="hidden" name="id" value="{{ $user->id }}" id="">

                        <div class="row">
                            <!-- Name -->
                            <div class="form-group col-md-6">
                                <label for="name" class="col-form-label">Name</label>
                                <input id="name" type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                            </div>

                            <!-- Email -->
                            <div class="form-group col-md-6">
                                <label for="email" class="col-form-label">Email</label>
                                <input id="email" type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                            </div>

                            <!-- Password (Optional Change) -->
                            <div class="form-group col-md-6">
                                <label for="password" class="col-form-label">Password (Leave blank to keep old)</label>
                                <input id="password" type="password" class="form-control" name="password">
                            </div>

                            <!-- Role -->
                            <div class="form-group col-md-6">
                                <label for="role" class="col-form-label">Role</label>
                                <select name="role" id="role" class="form-control" required>
                                    <option value="1" {{ $user->role == 1 ? 'selected' : '' }}>Manager</option>
                                    {{-- You can add more roles here --}}
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Update User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
