@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Target Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateModal">
            Create Target
        </button>
    </div>

    <div class="filter-sec target-filter">
        <div class="row">
            <div class="col-md-3">
                <label>Employee Type</label>
                <select class="form-control" id="filter_employee_type">
                    <option value="">-Select Employee Type-</option>
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Employee</label>
                <select class="form-control" id="filter_employee">
                    <option value="">-Select Employee-</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Target Year</label>
                <select class="form-control" id="filter_year">
                    @php
                        $currentYear = date('Y');
                        for ($i = 0; $i < 5; $i++) {
                            echo '<option value="' . ($currentYear + $i) . '">' . ($currentYear + $i) . '</option>';
                        }
                    @endphp
                </select>
            </div>
            <div class="col-md-3">
                <label>Target Month</label>
                <select class="form-control" id="filter_month">
                    <option value="">-Select Month-</option>
                    @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="targetTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Type</th>
                    <th>Employee Name</th>
                    <th>Target By Year</th>
                    <th>Target By Month</th>
                    <th>Unique Lead</th>
                    <th>Customer Visit</th>
                    <th>Aashiyana Count</th>
                    <th>Targets in Tons</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.target.modal-create-edit')

@include('admin.target.modal-view')

@endsection 
@section('scripts')
<script>
// Targets
$(document).ready(function () {
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
});
</script>
@endsection
