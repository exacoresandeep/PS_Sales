@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Activity Type Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateActivityTypeModal">
            Create
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="activityTypeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Activity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Include modal for creating/editing activity type -->
@include('admin.activity.type-modal-create-edit')

@endsection
