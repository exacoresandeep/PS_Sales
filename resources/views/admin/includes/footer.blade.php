{{-- <footer class="text-center mt-4">
    <p>&copy; {{ date('Y') }} Prabhu Steels. All rights reserved.</p>
</footer> --}}
  <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> --}}
  {{-- <script src="{{ asset('js/vendor/popper.min.js') }}"></script> --}}
  <!-- Add Popper.js Before Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>

<!-- Bootstrap (If Used) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- <script src="{{ asset('js/bootstrap.min.js') }}"></script> --}}


<!-- Bootstrap JS (Load after jQuery) -->

<!-- Bootstrap 5 JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.3/dist/alpine.min.js" defer></script>

    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>

$(document).ready(function () {
    $(".menu-title").click(function () {
        var $submenu = $(this).next(".submenu"); // Target the next UL (submenu)
        
        if ($submenu.is(":visible")) {
            $submenu.slideUp(); // Hide submenu
            $(this).find(".icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
        } else {
            $(".submenu").slideUp(); // Close all other open menus
            $(".menu-title .icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
            
            $submenu.slideDown(); // Show clicked submenu
            $(this).find(".icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
        }
    });

    $(".submenu a.active").each(function () {
        $(this).closest(".submenu").slideDown();
        $(this).closest("li").find(".menu-title .icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
    });

    // Targets

    $('#openCreateModal').click(function () {
        $('#targetForm')[0].reset(); 
        $('#target_id').val(''); 
        $('#createEditModalLabel').text('Create Target');
        $('#createEditModal').modal('show'); 
    });
     
    $('#targetForm').submit(function (e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let targetId = $('#target_id').val();
        let url = targetId ? "{{ route('admin.target.update') }}" : "{{ route('admin.target.store') }}";

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Swal.fire('Success', response.message, 'success');
                $('#createEditModal').modal('hide'); 
                $('#targetTable').DataTable().ajax.reload();
            },
            error: function (xhr) {
                Swal.fire('Error', 'Could not save target.', 'error');
            }
        });
    });
    
    var table = $('#targetTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('admin.target.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.employee_type = $('#filter_employee_type').val();
                d.employee_id = $('#filter_employee').val();
                d.year = $('#filter_year').val();
                d.month = $('#filter_month').val();
                console.log("Sent Data:", d); 
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee_type', name: 'employee_type' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'year', name: 'year' },
            { data: 'month', name: 'month' },
            { data: 'unique_lead', name: 'unique_lead' },
            { data: 'customer_visit', name: 'customer_visit' },
            { data: 'aashiyana', name: 'aashiyana' },
            { data: 'order_quantity', name: 'order_quantity' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    
    $('#employee_type').change(function () {
        let employeeTypeId = $(this).val();
        $('#employee_id').html('<option value="">Loading...</option>');

        if (employeeTypeId) {
            $.get("{{ route('admin.getEmployees', '') }}/" + employeeTypeId, function (response) {
                $('#employee_id').html('<option value="">-Select Employee-</option>');
                $.each(response, function (index, employee) {
                    $('#employee_id').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                });
            }).fail(function () {
                Swal.fire('Error', 'Could not load employees.', 'error');
            });
        } else {
            $('#employee_id').html('<option value="">-Select Employee-</option>');
        }
    });
    $('.target-filter select').change(function () {
        table.ajax.reload();
    });

    $('#filter_employee_type').change(function () {
        let employeeTypeId = $(this).val();
        $('#filter_employee').html('<option value="">Loading...</option>');

        if (employeeTypeId) {
            $.get("{{ route('admin.getEmployees', '') }}/" + employeeTypeId, function (response) {
                $('#filter_employee').html('<option value="">-Select Employee-</option>');
                $.each(response, function (index, employee) {
                    $('#filter_employee').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                });
            });
        } else {
            $('#filter_employee').html('<option value="">-Select Employee-</option>');
        }
    });
    
    function deleteTarget(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.target.delete', '') }}/" + id,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            $('#targetTable').DataTable().ajax.reload(); // Reload DataTable
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not delete target.', 'error');
                    }
                });
            }
        });
    }

    window.handleAction = function (id, action) {
        $.get("{{ route('admin.target.get', ':id') }}".replace(':id', id), function (response) {

            if (action === 'edit') {
                $('#createEditModalLabel').text('Edit Target');
                $('#target_id').val(id);
                $('#employee_type').val(response.target.employee.employee_type_id).trigger('change');

                setTimeout(() => {
                    $('#employee_id').val(response.target.employee_id);
                }, 500);

                $('#year').val(response.target.year);
                $('#month').val(response.target.month);
                $('#unique_lead').val(response.target.unique_lead);
                $('#customer_visit').val(response.target.customer_visit);
                $('#aashiyana').val(response.target.aashiyana);
                $('#order_quantity').val(response.target.order_quantity);

                $('#createEditModal').modal('show');
            } else if (action === 'view') {

                $('#view_employee_type').text(response.target.employee.employee_type.type_name || '-');
                $('#view_employee_name').text(response.target.employee.name || '-');
                $('#view_year').text(response.target.year || '-');
                $('#view_month').text(response.target.month || '-');
                $('#view_unique_lead').text(response.target.unique_lead || '0');
                $('#view_customer_visit').text(response.target.customer_visit || '0');
                $('#view_aashiyana').text(response.target.aashiyana || '0');
                $('#view_order_quantity').text(response.target.order_quantity || '0');

                $('#viewModal').modal('show');
            }
        }).fail(function () {
            Swal.fire('Error', 'Could not fetch target details.', 'error');
        });
    };


    window.deleteTarget = deleteTarget;

    // Routes
    $('#openCreateRouteModal').click(function () {
        $('#routeForm')[0].reset(); 
        $('#route_id').val(''); 
        $('#createEditRouteModalLabel').text('Create Target');
        $('#createEditRouteModal').modal('show'); 
    });



    // var subLocationInput = new Choices('#sub-locations', {
    //     delimiter: ',',             
    //     editItems: true,            
    //     removeItemButton: true,    
    //     paste: false,             
    //     duplicateItemsAllowed: false, 
    //     placeholderValue: 'Enter sub-locations',
    //     searchPlaceholderValue: 'Search sub-location',
    // });
    let subLocationInput;
    $('#createEditRouteModal').on('shown.bs.modal', function () {
        if (!subLocationInput) {
            subLocationInput = new Choices('#sub-locations', {
                delimiter: ',',
                editItems: true,
                removeItemButton: true,
                paste: false,
                duplicateItemsAllowed: false,
                placeholderValue: 'Enter sub-locations',
                searchPlaceholderValue: 'Search sub-location'
            });
        }
    });



    $('#createEditRouteModal').on('hidden.bs.modal', function () {
        subLocationInput.clearStore();  
        subLocationInput.clearInput();  
    });

    $('#routeForm').submit(function (e) {
        
        e.preventDefault();
        let subLocationsArray = subLocationInput.getValue(true);
        $('#sub-locations').val(JSON.stringify(subLocationsArray)); // Convert array to JSON string

        // Serialize the form data
        let formData = $(this).serialize();
        let routeId = $('#route_id').val();
        let url = routeId ? "{{ route('admin.route.update') }}" : "{{ route('admin.route.store') }}";
        
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Swal.fire('Success', response.message, 'success');
                $('#createEditRouteModal').modal('hide');  // Hide the modal
                $('#routeTable').DataTable().ajax.reload();  // Reload the route table

                // Reset the sub-location input after saving
                subLocationInput.clearStore();
                subLocationInput.clearInput();
            },
            error: function (xhr) {
                Swal.fire('Error', 'Could not save route.', 'error');
            }
        });
    });

    var table = $('#routeTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('admin.route.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.district_id = $('#filter_district').val();
                d.route_name = $('#filter_route').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'district_name', name: 'district_name' }, 
            { data: 'route_name', name: 'route_name' },
            { data: 'location_name', name: 'location_name' },
            { data: 'sub_locations', name: 'sub_locations' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#filter_district').change(function () {
        let districtId = $(this).val();
        $('#filter_route').html('<option value="">Loading...</option>');

        if (districtId) {
            $.get("{{ route('admin.route.getAllRoutesByDistrict', '') }}/" + districtId, function (response) {
                $('#filter_route').html('<option value="">-Select Route-</option>');
                $.each(response, function (index, route) {
                    $('#filter_route').append('<option value="' + route.id + '">' + route.route_name + '</option>');
                });
            });
        } else {
            $('#filter_route').html('<option value="">-Select Route-</option>');
        }
    });


    //Activity Type
    var table = $('#activityTypeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.activity.activity-type-list') }}",
            data: function (d) {
                d.status = $('#statusFilter').val(); 
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, 
            { data: 'name', name: 'name' },
            {
                data: 'status',
                name: 'status',
                render: function (data) {
                    return data == 1 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-danger">Inactive</span>';
                }
            },
            {
                data: 'id',
                name: 'action',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm editActivityType" data-id="${data}">Edit</button>
                        <button class="btn btn-danger btn-sm deleteActivityType" data-id="${data}">Delete</button>
                    `;
                }
            }
        ]
    });


    // Open Create Modal
    $('#openCreateActivityTypeModal').click(function () {
        $('#activityTypeForm')[0].reset(); 
        $('#activity_type_id').val('');
        $('#createEditActivityTypeModalLabel').text('Create Activity Type');
        $('#createEditActivityTypeModal').modal('show');
    });

    // Handle Create / Update
    $('#activityTypeForm').submit(function (e) {
        e.preventDefault();
        
        let id = $('#activity_type_id').val();
        let url = id ? `/admin/activity/activity-type-update/${id}` : "/admin/activity/activity-type-store";
        let method = id ? 'PUT' : 'POST';
        
        let formData = $(this).serialize(); // Get form data
        if (id) {
            formData += '&_method=PUT'; // ✅ Add `_method` for PUT request
        }

        $.ajax({
            url: url,
            type: 'POST',  // ✅ Always use POST and add `_method` for PUT
            data: formData,
            success: function (response) {
                $('#createEditActivityTypeModal').modal('hide');
                $('#activityTypeForm')[0].reset();
                table.ajax.reload();
            },
            error: function (xhr) {
                alert('Error: ' + xhr.responseJSON.message);
            }
        });
    });

    // Edit Activity Type
    $(document).on('click', '.editActivityType', function () {
        let id = $(this).data('id');

        $.ajax({
            url: `/admin/activity/activity-type-edit/${id}`,
            type: 'GET',
            success: function (response) {
                $('#activity_type_id').val(response.activity_type.id);
                $('#activity_name').val(response.activity_type.name);
                $('#status').val(response.activity_type.status);
                $('#createEditActivityTypeModal').modal('show');
            },
            error: function () {
                alert('Error fetching activity type details.');
            }
        });
    });

    // Delete Activity Type
    $(document).on('click', '.deleteActivityType', function () {
        let id = $(this).data('id');

        if (confirm('Are you sure you want to delete this Activity Type?')) {
            $.ajax({
                url: `/admin/activity/activity-type-delete/${id}`,
                type: 'DELETE',  // ✅ Use DELETE method directly
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // ✅ Include CSRF Token
                },
                success: function (response) {
                    alert(response.message);
                    table.ajax.reload();
                },
                error: function (xhr) {
                    alert('Error deleting activity type: ' + xhr.responseText);
                }
            });
        }
    });

});




</script>
</body>
</html>