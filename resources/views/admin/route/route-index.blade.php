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
            ajax: '{{ route("admin.route.route-list") }}',
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'district_name', name: 'district.name' },
                { data: 'locations', name: 'locations' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#openCreateRouteModal').click(function () {
            $('#createEditRouteModal').modal('show');
        });

        $('#routeForm').on('submit', function (e) {
            e.preventDefault();

            let route_id = $('#route_id').val();
            let url = route_id
                ? `{{ route('admin.route.route-update', ':id') }}`.replace(':id', route_id)
                : "{{ route('admin.route.route-store') }}";

            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            if (route_id) formData.append('_method', 'PUT');
            formData.append('district', $('#district').val());

            let locations = locationInput.getValue().map(loc => loc.value);
            locations.forEach(location => formData.append('locations[]', location));

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    alert(response.message);
                    routeTable.ajax.reload();
                    $('#createEditRouteModal').modal('hide');
                    $('#routeForm')[0].reset();
                },
                error: function (xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });

        $(document).on('click', '.editRoute', function () {
            let route_id = $(this).data('id');

            $.ajax({
                url: `{{ route('admin.route.route-edit', ':id') }}`.replace(':id', route_id),
                type: 'GET',
                success: function (response) {
                    const route = response.route;

                    $('#route_id').val(route.id);
                    $('#district').val(route.district_id);

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

                    // Clear & Set Locations
                    locationInput.clearStore();
                    locationInput.clearInput();
                    setTimeout(() => {
                        route.locations.forEach(loc => locationInput.setValue([loc]));
                    }, 100);

                    $('#createEditRouteModalLabel').text('Edit Route');
                    $('.submit-btn').text('Update');
                    $('#createEditRouteModal').modal('show');
                },
                error: function (xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });

        // $(document).on('click', '.editRoute', function () {
        //     let route_id = $(this).data('id');

        //     $.ajax({
        //         url: `{{ route('admin.route.route-edit', ':id') }}`.replace(':id', route_id),
        //         type: 'GET',
        //         success: function (response) {
        //             console.log(response); // Debugging: Check if route data is received

        //             const route = response.route;

        //             if (route) {
        //                 $('#route_id').val(route.id); // Ensure the ID is set correctly
        //                 $('#district_id').val(route.district_id);
        //                 $('#route_name').val(route.route_name);
        //                 $('#location_name').val(route.location_name);
        //                 $('#sub-locations').val(route.sub_locations.join(', ')); // Assuming it's an array

        //                 $('#createEditRouteModalLabel').text('Edit Route');
        //                 $('.submit-btn').text('Update');
        //                 $('#createEditRouteModal').modal('show');
        //             } else {
        //                 alert('Error: Route data not found.');
        //             }
        //         },
        //         error: function (xhr) {
        //             console.error('Error:', xhr.responseJSON.message);
        //             alert('Error fetching route details.');
        //         }
        //     });
        // });


        // Delete Route
        $(document).on('click', '.deleteRoute', function () {
            const route_id = $(this).data('id');
            if (confirm('Are you sure you want to delete this route?')) {
                $.ajax({
                    url: `{{ route('admin.route.route-delete', ':id') }}`.replace(':id', route_id),
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        alert(response.message);
                        routeTable.ajax.reload();
                    },
                    error: function (xhr) {
                        alert('Error: ' + xhr.responseJSON.message);
                    }
                });
            }
        });
    });

</script>
@endsection
