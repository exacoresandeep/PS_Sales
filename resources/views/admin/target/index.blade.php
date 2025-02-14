@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Target Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateModal">
            Create Target
        </button>
    </div>

    <!-- Filter Section -->
    <div class="filter-sec">
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

    <!-- DataTable Section -->
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

<!-- Create/Edit Modal -->
@include('admin.target.modal-create-edit')

<!-- View Modal -->
@include('admin.target.modal-view')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    // Load DataTable
    // var table = $('#targetTable').DataTable({
    //     processing: true,
    //     serverSide: true,
    //     ajax: {
    //         url: "{{ route('admin.target.get') }}",
    //         type: 'POST',
    //         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    //         data: function (d) {
    //             d.employee_type = $('#filter_employee_type').val();
    //             d.employee_id = $('#filter_employee').val();
    //             d.year = $('#filter_year').val();
    //             d.month = $('#filter_month').val();
    //         }
    //     },
    //     columns: [
    //         { data: null, render: function (data, type, row, meta) { return meta.row + 1; } },
    //         { data: 'employee_type', name: 'employee_type' },
    //         { data: 'employee_name', name: 'employee_name' },
    //         { data: 'year', name: 'year' },
    //         { data: 'month', name: 'month' },
    //         { data: 'unique_lead', name: 'unique_lead' },
    //         { data: 'customer_visit', name: 'customer_visit' },
    //         { data: 'aashiyana', name: 'aashiyana' },
    //         { data: 'order_quantity', name: 'order_quantity' },
    //         { data: 'action', name: 'action', orderable: false, searchable: false }
    //     ]
    // });

    // // Filter Change Event
    // $('.filter-sec select').change(function () {
    //     table.ajax.reload();
    // });
    var table = $('#targetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.target.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.employee_type = $('#filter_employee_type').val();
                d.employee_id = $('#filter_employee').val();
                d.year = $('#filter_year').val();
                d.month = $('#filter_month').val();
            }
        },
        columns: [
            { data: null, render: function (data, type, row, meta) { return meta.row + 1; } },
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

    // Reload DataTable when filters change
    $('.filter-sec select').change(function () {
        table.ajax.reload();
    });

    // Load Employees based on Employee Type
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

    // Delete Target
    function deleteTarget(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('admin.target.delete', '') }}/" + id, {
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function (response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    table.ajax.reload();
                }).fail(function () {
                    Swal.fire('Error', 'Could not delete target.', 'error');
                });
            }
        });
    }

    // Global function for edit/view actions
    window.handleAction = function (id, action) {
        $.get("{{ route('admin.target.get', '') }}/" + id, function (response) {
            if (action === 'edit') {
                $('#createEditModalLabel').text('Edit Target');
                $('#createEditModal input, #createEditModal select').each(function () {
                    $(this).val(response[$(this).attr('name')]);
                });
                $('#createEditModal').modal('show');
            } else if (action === 'view') {
                $('#viewModalLabel').text('Target Details');
                $('#viewModalBody').html(response.viewContent);
                $('#viewModal').modal('show');
            }
        }).fail(function () {
            Swal.fire('Error', 'Could not fetch target details.', 'error');
        });
    };

    // Bind Delete Function
    window.deleteTarget = deleteTarget;
});
</script>
@endsection
