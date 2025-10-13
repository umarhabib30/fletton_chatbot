@extends('layouts.admin')
@section('style')
    <style>
        .badge-default-yes {
            background-color: #C1EC4A !important;
            color: #1A202C !important;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 8px;
        }

        .badge-default-no {
            background-color: #1A202C !important;
            color: #FFFFFF !important;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 8px;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <!-- ============================================================== -->
        <!-- basic table  -->
        <!-- ============================================================== -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">Basic Table</h5>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered first">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Template ID</th>
                                    <th>Is Default</th>
                                    <th>Used For</th>
                                    <th>Action</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $template)
                                    <tr>
                                        <td>{{ $template->title }}</td>
                                        <td>{{ $template->template_id }}</td>
                                        <td>
                                            @if ($template->is_default)
                                                <span class="badge badge-default-yes">Yes</span>
                                            @else
                                                <span class="badge badge-default-no">No</span>
                                            @endif
                                        </td>

                                        <td>{{ $template->used_for }}</td>
                                        <td><a href="{{ url('admin/template/edit/' . $template->id) }}" class="btn btn-primary">Edit</a></td>
                                        <td>
                                            <a href="javascript:void(0);" class="btn btn-danger delete-btn"
                                                data-url="{{ url('admin/template/delete/' . $template->id) }}">
                                                Delete
                                            </a>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- end basic table  -->
        <!-- ============================================================== -->
    </div>
@endsection
@section('script')
<script>
    $(document).on('click', '.delete-btn', function() {
        let deleteUrl = $(this).data('url');

        Swal.fire({
            title: "Are you sure?",
            text: "This template will be permanently deleted!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#C1EC4A",
            cancelButtonColor: "#1A202C",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl; // Redirect to delete route
            }
        });
    });
</script>
@endsection

