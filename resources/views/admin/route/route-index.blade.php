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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.route.route-modal-create-edit')

@endsection
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
@section('scripts')
<script>
    $(document).ready(function () {

        let locationInput;

        $('#createEditRouteModal').on('shown.bs.modal', function () {
            if (!locationInput) {
                locationInput = new Choices('#locations', {
                    delimiter: ',',
                    editItems: true,
                    removeItemButton: true,
                    paste: false,
                    duplicateItemsAllowed: false,
                    placeholderValue: 'Enter locations',
                    searchPlaceholderValue: 'Search location'
                });
            }
        });

        $('#createEditRouteModal').on('hidden.bs.modal', function () {
            $('#routeForm')[0].reset(); 
            $('#route_id').val(''); 
            $('.submit-btn').text('Create');
            $('#createEditRouteModalLabel').text('Create Route');

            if (locationInput) {
                locationInput.clearStore();  
                locationInput.clearInput();  
            }
        });

        let routeTable = $('#routeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.route.route-list') }}',
            columns: [
                { 
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex', 
                    orderable: false, 
                    searchable: false 
                },
                { data: 'district_name', name: 'district.name' },  
                { data: 'locations', name: 'locations' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#openCreateRouteModal').click(function () {
            $('#createEditRouteModal').modal('show');
        });

        // Create / Update Route Form Submission
        $('#routeForm').on('submit', function (e) {
            e.preventDefault();

            const route_id = $('#route_id').val();
            const district = $('#district').val();
            const locations = locationInput.getValue().map(loc => loc.value); // Get array of locations

            const url = route_id 
                ? `{{ url('routes/route-update') }}/${route_id}`
                : `{{ route('admin.route.route-store') }}`;
            const method = route_id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: {
                    district: district,
                    locations: locations, 
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert(response.message);
                    routeTable.ajax.reload();
                    $('#createEditRouteModal').modal('hide');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });

        // window.editRoute = function(route_id) {
        //     $.get(`{{ url('routes/route-edit') }}/${route_id}`, function(response) {
        //         const route = response.route;

        //         $('#route_id').val(route.id);
        //         $('#district').val(route.district_id);

        //         if (locationInput) {
        //             locationInput.clearStore(); // Clear previous values
        //             route.locations.forEach(loc => locationInput.setValue([loc])); // Add new values
        //         }

        //         $('#createEditRouteModalLabel').text('Edit Route');
        //         $('.submit-btn').text('Update');
        //         $('#createEditRouteModal').modal('show');
        //     });
        // };
        $(document).on('click', '.editRoute', function () {
            let route_id = $(this).data('id'); // Get the route ID

            $.ajax({
                url: `{{ url('routes/route-edit') }}/${route_id}`,
                type: 'GET',
                success: function(response) {
                    const route = response.route;

                    $('#route_id').val(route.id);
                    $('#district').val(route.district_id);

                    if (locationInput) {
                        locationInput.clearStore();
                        route.locations.forEach(loc => locationInput.setValue([loc]));
                    }

                    $('#createEditRouteModalLabel').text('Edit Route');
                    $('.submit-btn').text('Update');
                    $('#createEditRouteModal').modal('show');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });


     
        $(document).on('click', '.deleteRoute', function() {
            const route_id = $(this).data('id');
            if (confirm('Are you sure you want to delete this route?')) {
                $.ajax({
                    url: `{{ url('routes/route-delete') }}/${route_id}`,
                    method: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(response) {
                        alert(response.message);
                        routeTable.ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            }
        });


    });
</script>
@endsection
