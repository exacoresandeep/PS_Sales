@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Route Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateRouteModal">
            Create
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="routeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>District</th>
                    <th>Locations</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.route.route-modal-create-edit')

@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@section('scripts')
<script>
    $(document).ready(function () {
    let routeTable = $('#routeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.route.route-list') }}',
        columns: [
            {data: 'id', name: 'id'},
            {data: 'district.name', name: 'district.name'}, 
            {data: 'locations', name: 'locations', render: function(data) {
                return data.join(', '); 
            }},
            {data: 'status', name: 'status'},
            {data: 'id', name: 'id', render: function(data) {
                return `
                    <button class="btn btn-sm btn-warning" onclick="editRoute(${data})" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteRoute" data-id="${data}" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
            }},
        ],
    });

    $('#openCreateRouteModal').click(function () {
        $('#routeForm')[0].reset();
        $('#route_id').val('');
        $('.submit-btn').text('Create');
        $('#createEditRouteModalLabel').text('Create Route');
        $('#createEditRouteModal').modal('show');
    });

    $('#routeForm').on('submit', function (e) {
        e.preventDefault();

        const district = $('#district').val();
        const locations = $('#locations').val().split(',').map(loc => loc.trim()); 

        $.ajax({
            url: '{{ route('admin.route.route-store') }}',
            method: 'POST',
            data: {
                district: district,
                locations: locations, 
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert('Route created successfully!');
                routeTable.ajax.reload();
                $('#createEditRouteModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert('Error creating route: ' + error);
            }
        });
    });

    // Edit Route
    window.editRoute = function(route_id) {
        $.get('{{ url('routes/route-edit') }}/' + route_id, function(response) {
            const route = response.route;
            const districts = response.districts;

            $('#route_id').val(route.id);
            $('#district').val(route.district_id);
            $('#locations').val(route.locations.join(', '));  

            $('#createEditRouteModalLabel').text('Edit Route');
            $('.submit-btn').text('Update');
            $('#createEditRouteModal').modal('show');
        });
    };

    // Delete Route
    $(document).on('click', '.deleteRoute', function() {
        const route_id = $(this).data('id');
        if (confirm('Are you sure you want to delete this route?')) {
            $.ajax({
                url: '{{ url('routes/route-delete') }}/' + route_id,
                method: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    alert('Route deleted successfully!');
                    routeTable.ajax.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error deleting route: ' + error);
                }
            });
        }
    });
});

</script>
@endsection
