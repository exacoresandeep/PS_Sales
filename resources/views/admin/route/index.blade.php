@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Route Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateRouteModal">
            Create Route
        </button>
    </div>

    <div class="filter-sec">
        <div class="row">
            <div class="col-md-3">
                <label>District</label>
                <select class="form-control" id="filter_district">
                    <option value="">-Select District-</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Route</label>
                <select class="form-control" id="filter_route">
                    <option value="">-Select Route-</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-primary w-100" id="searchRoute">Search</button>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="routeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>District</th>
                    <th>Route Name</th>
                    <th>Route Location</th>
                    <th>Sub Locations</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('admin.route.modal-create-edit')
{{-- @include('admin.route.modal-view') --}}

@endsection

