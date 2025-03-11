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
@section('scripts')
<script>
    $(document).ready(function() {
        $('#ordersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('accounts.orders.list') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
                { data: 'date', name: 'date' },
                { data: 'order_id', name: 'order_id' },
                { data: 'dealer_name', name: 'dealer_name' },
                { data: 'dealer_code', name: 'dealer_code' },
                { data: 'employee_type', name: 'employee_type' },
                { data: 'employee_name_code', name: 'employee_name_code' },
                { data: 'amount', name: 'amount' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            error: function(xhr, error, code) {
                console.log("Error in DataTables:", xhr, error, code);
            }
        });
        $(document).on('click', '.approve-order', function() {
            let orderId = $(this).data('id');
            
            $.ajax({
                url: '/accounts/orders/approve/' + orderId,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    alert('Order Approved!');
                    $('#ordersTable').DataTable().ajax.reload();
                }
            });
        });

        $(document).on('click', '.reject-order', function() {
            let orderId = $(this).data('id');

            $.ajax({
                url: '/accounts/orders/reject/' + orderId,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    alert('Order Rejected!');
                    $('#ordersTable').DataTable().ajax.reload();
                }
            });
        });

    });
</script>
@endsection
