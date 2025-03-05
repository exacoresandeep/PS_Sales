@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Activity Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateActivityModal" data-bs-toggle="modal" data-bs-target="#createEditActivityModal">
            Create Activity
        </button>
    </div>

    <div class="filter-sec">
        <div class="row">
            <div class="col-md-3">
                <label>Activity Type</label>
                <select class="form-control" id="filter_activity_type">
                    <option value="">-Select Activity Type-</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Dealer</label>
                <input type="text" class="form-control" id="filter_dealer" placeholder="Search by Dealer Name/Code">
            </div>
            <div class="col-md-3">
                <label>District</label>
                <select class="form-control" id="filter_district">
                    <option value="">-Select District-</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->district }}">{{ $district->district }}</option>
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
                <label>Assigned Date</label>
                <input type="date" class="form-control" id="filter_assigned_date">
            </div>
            <div class="col-md-3">
                <label>Due Date</label>
                <input type="date" class="form-control" id="filter_due_date">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary mt-4" id="filterSearch">Search</button>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="activityTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Activity Type</th>
                    <th>Dealer Name</th>
                    <th>Dealer Code</th>
                    <th>Assigned Employee</th>
                    <th>Assigned Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.activity.modal-create-edit')

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        var table = $('#activityTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.activity.list') }}",
                data: function (d) {
                    d.activity_type = $('#filter_activity_type').val();
                    d.dealer = $('#filter_dealer').val();
                    d.district = $('#filter_district').val();
                    d.employee = $('#filter_employee').val();
                    d.assigned_date = $('#filter_assigned_date').val();
                    d.due_date = $('#filter_due_date').val();
                }
            },
            columns: [
                { data: null, render: function (data, type, row, meta) { return meta.row + 1; } },
                { data: 'activityType.name', name: 'activityType.name' },
                { data: 'dealer.dealer_name', name: 'dealer.dealer_name' },
                { data: 'dealer.dealer_code', name: 'dealer.dealer_code' },
                { data: 'employee.name', name: 'employee.name' },
                { data: 'assigned_date', name: 'assigned_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'status', name: 'status' },
                { data: 'id', render: function (data) {
                    return `<button class="btn btn-warning editActivity" data-id="${data}">Edit</button>
                            <button class="btn btn-danger deleteActivity" data-id="${data}">Delete</button>`;
                }}
            ]
        });

        $('#filterSearch').click(function () {
            table.ajax.reload();
        });

        $(document).on('click', '.deleteActivity', function () {
            let id = $(this).data('id');
            if (confirm('Are you sure you want to delete this Activity?')) {
                $.ajax({
                    url: `/admin/activity/delete/${id}`,
                    type: 'DELETE',
                    success: function (response) {
                        alert(response.message);
                        table.ajax.reload();
                    },
                    error: function () {
                        alert('Error deleting activity.');
                    }
                });
            }
        });
    });
</script>
@endsection
