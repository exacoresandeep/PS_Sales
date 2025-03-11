@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Order Management</h3>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="ordersTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Date</th>
                    <th>Order ID</th>
                    <th>Dealer Name</th>
                    <th>Dealer Code</th>
                    <th>Emp Type</th>
                    <th>Emp Name - Code</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- @include('sales.route.modal-create-edit') --}}

@endsection
