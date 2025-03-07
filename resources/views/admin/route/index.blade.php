@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Assigned Route Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateAssignRouteModal">Create</button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="routeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>District</th>
                    <th>Employee Type</th>
                    <th>Employee</th>
                    <th>Route Names</th>
                    <th>Locations</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.route.modal-create-edit')

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
      
        function initializeSelect2() {
            $('.select2').select2({ width: '100%', dropdownParent: $('#createEditAssignRouteModal') });
            $('.select2-multi').select2({ width: '100%', dropdownParent: $('#createEditAssignRouteModal'), placeholder: "Select Locations", allowClear: true });
        }

        initializeSelect2();
        
        function updateRouteDropdowns() {
            let selectedRoutes = [];

            $('select[name^="routes"]').each(function () {
                let val = $(this).val();
                if (val) selectedRoutes.push(val);
            });

            $('select[name^="routes"]').each(function () {
                let currentSelect = $(this);
                let currentValue = currentSelect.val();

                currentSelect.find('option').each(function () {
                    let optionValue = $(this).val();

                    if (optionValue && selectedRoutes.includes(optionValue) && optionValue !== currentValue) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
            });
        }
     
        function updateLocationDropdowns() {
            let selectedLocations = new Set();

            $('.select2-multi').each(function () {
                let values = $(this).val();
                if (values) {
                    values.forEach(value => selectedLocations.add(value));
                }
            });

            // Update all dropdowns to disable selected locations
            $('.select2-multi').each(function () {
                let currentSelect = $(this);
                let currentValue = new Set(currentSelect.val() || []);

                currentSelect.find('option').each(function () {
                    let optionValue = $(this).val();

                    if (optionValue && selectedLocations.has(optionValue) && !currentValue.has(optionValue)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });

                // Refresh select2 to apply changes
                currentSelect.trigger('change.select2');
            });
        }
        $(document).on('change', '.select2-multi', function () {
            updateLocationDropdowns();
        });

        $(document).on('change', 'select[name^="routes"]', function () {
            updateRouteDropdowns();
        });
   

        function loadDistricts() {
            $.get("{{ route('admin.get-districts') }}", function (data) {
                $('#district').empty().append('<option value="">Select District</option>');
                $.each(data, function (key, district) {
                    $('#district').append(`<option value="${district.id}">${district.name}</option>`);
                });
            });
        }
        $('#district, #employee_type').on('change', function () {
            let district_id = $('#district').val();
            let employee_type_id = $('#employee_type').val();

            if (employee_type_id == 1) { 
                $('#asoField').show();
                $('#aso').attr('required', true);
                if (district_id) {
                    $.get("{{ route('admin.get-employees') }}", { district_id: district_id, employee_type_id: 2 }, function (data) {
                        $('#aso').empty().append('<option value="">Select Area Sales Officer</option>');
                        $.each(data, function (key, employee) {
                            $('#aso').append(`<option value="${employee.id}">${employee.name}</option>`);
                        });
                    });
                }
            } else {
                $('#asoField').hide();
                $('#aso').removeAttr('required'); 
                $('#aso').val("").trigger('change');
            }

            if (district_id && employee_type_id) {
                $.get("{{ route('admin.get-employees') }}", { district_id, employee_type_id }, function (data) {
                    $('#employee').empty().append('<option value="">Select Employee</option>');
                    $.each(data, function (key, employee) {
                        $('#employee').append(`<option value="${employee.id}">${employee.name}</option>`);
                    });
                });
            }
            if (district_id) {
                $.get("{{ route('admin.get-locations') }}", { district_id: district_id }, function (data) {
                    $('.select2-multi').each(function () {
                        let $select = $(this);
                        $select.empty();

                        if (data.length > 0) {
                            $.each(data, function (index, location) {
                                $select.append(`<option value="${location}">${location}</option>`);
                            });
                        } else {
                            $select.append('<option value="">No Locations Available</option>');
                        }

                        // Trigger change and reapply disabled logic
                        $select.trigger('change.select2');
                        updateLocationDropdowns();
                    });
                });
            } else {
                $('.select2-multi').empty().trigger('change.select2');
            }


            // if (district_id) {
            //     $.get("{{ route('admin.get-locations') }}", { district_id: district_id }, function (data) {
            //         $('.select2-multi').empty();
            //         if (data.length > 0) {
            //             $.each(data, function (index, location) {
            //                 $('.select2-multi').append('<option value="' + location + '">' + location + '</option>');
            //             });
            //         } else {
            //             $('.select2-multi').append('<option value="">No Locations Available</option>');
            //         }
            //         $('.select2-multi').trigger('change'); 
            //     });
            // } else {
            //     $('.select2-multi').empty().trigger('change');
            // }
        });



        let routeTable = $('#routeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.route.assigned-list') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
                { data: 'district.name', name: 'district.name' },
                { data: 'employee_type', name: 'employee_type' },
                { data: 'employee.name', name: 'employee.name' },
                { data: 'route_names', name: 'route_names' },
                { data: 'locations', name: 'locations' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#openCreateAssignRouteModal').on('click', function () {
            $('#assignedRouteForm')[0].reset();
            $('.select2').val(null).trigger('change');
            // $('.select2-multi').val([]).trigger('change');

            $('.select2-multi').each(function () {
                $(this).find('option').prop('disabled', false); // Re-enable all options
                $(this).val([]).trigger('change'); // Clear selection
            });            
            updateRouteDropdowns();
            updateLocationDropdowns();
            $('#createEditAssignRouteModal').modal('show');
        });

        $(document).on('click', '.editRoute', function () {
            let routeId = $(this).data('id');
            $.get("{{ url('admin/routes/edit') }}/" + routeId, function (data) {
                $('#route_id').val(data.id);
                $('#district').val(data.district_id).trigger('change');
                $('#employee_type').val(data.employee_type_id).trigger('change');
                $('#employee').val(data.employee_id).trigger('change');
                $('#aso').val(data.aso_id).trigger('change');
                $('#route_names').val(data.route_names).trigger('change');
                $('#locations').val(data.locations).trigger('change');
                $('#createEditAssignRouteModal').modal('show');
            });
        });

        $('#assignedRouteForm').on('submit', function (e) {
            e.preventDefault();

            let isValid = true;

            $('select[name^="routes"]').each(function () {
                if (!$(this).val()) {
                    $(this).attr('required', true); 
                    isValid = false;
                } else {
                    $(this).removeAttr('required');
                }
            });

            $('.select2-multi').each(function () {
                if (!$(this).val() || $(this).val().length === 0) {
                    $(this).attr('required', true);
                    isValid = false;
                } else {
                    $(this).removeAttr('required'); 
                }
            });

            if (!isValid) {
                return false; 
            }
            let routeId = $('#route_id').val().trim();
            
            let formData = new FormData(this);

            let url = routeId 
                ? "{{ route('admin.route.assigned-update', ':id') }}".replace(':id', routeId) 
                : "{{ route('admin.route.assigned-store') }}";

            let type = routeId ? "POST" : "POST"; 

            $.ajax({
                url: url,
                type: type,
                data: formData,
                processData: false,
                contentType: false, 
                success: function (response) {
                    alert(response.message);
                    $('#createEditAssignRouteModal').modal('hide');
                    routeTable.ajax.reload();
                },
                error: function (xhr) {
                    alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong'));
                }
            });
        });


        $(document).on('click', '.deleteRoute', function () {
            let routeId = $(this).data('id');
            if (confirm("Are you sure you want to delete this assigned route?")) {
                $.ajax({
                    url: "{{ url('admin/routes/delete') }}/" + routeId,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
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

        loadDistricts();
    });
</script>

@endsection
