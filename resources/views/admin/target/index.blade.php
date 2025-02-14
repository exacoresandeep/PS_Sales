@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Target Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateModal">
            Create Target
        </button>
    </div>

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
