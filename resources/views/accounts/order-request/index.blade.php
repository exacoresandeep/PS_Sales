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


@include('accounts.order-request.view')

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



        // $(document).on('click', '#approve_order', function () {
        //     let orderId = $('#view_order_id').text();
        //     let paymentTerm = $('#payment_term').val();

        //     if (!paymentTerm) {
        //         Swal.fire({
        //             icon: 'warning',
        //             title: 'Payment Term Required!',
        //             text: 'Please select a Payment Term before approving the order.'
        //         });
        //         return;
        //     }

        //     $.ajax({
        //         url: '/accounts/orders/approve/' + orderId,
        //         type: 'POST',
        //         data: {
        //             _token: $('meta[name="csrf-token"]').attr('content'),
        //             order_approved: 1, // Approved
        //             payment_type: paymentTerm
        //         },
        //         success: function (response) {
        //             Swal.fire({
        //                 icon: 'success',
        //                 title: 'Order Approved!',
        //                 text: 'The order has been successfully approved.'
        //             });

        //             $('#viewModal').modal('hide');
        //             $('#ordersTable').DataTable().ajax.reload();
        //         },
        //         error: function (xhr) {
        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Approval Failed!',
        //                 text: xhr.responseJSON?.message || 'Something went wrong!'
        //             });
        //         }
        //     });
        // });

        // $(document).on('click', '#reject_order', function () {
        //     Swal.fire({
        //         title: 'Are you sure you want to reject this order?',
        //         input: 'textarea',
        //         inputPlaceholder: 'Enter reason for rejection...',
        //         inputAttributes: {
        //             'aria-label': 'Enter reason for rejection'
        //         },
        //         showCancelButton: true,
        //         confirmButtonColor: '#d33',
        //         cancelButtonColor: '#6c757d',
        //         confirmButtonText: 'Reject',
        //         cancelButtonText: 'Cancel',
        //         preConfirm: (reason) => {
        //             if (!reason) {
        //                 Swal.showValidationMessage('Reason for rejection is required.');
        //             }
        //             return reason;
        //         }
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             let orderId = $('#view_order_id').text();

        //             $.ajax({
        //                 url: '/accounts/orders/reject/' + orderId,
        //                 type: 'POST',
        //                 data: {
        //                     _token: $('meta[name="csrf-token"]').attr('content'),
        //                     order_approved: 2, // Rejected
        //                     reason_for_rejection: result.value
        //                 },
        //                 success: function (response) {
        //                     Swal.fire({
        //                         icon: 'success',
        //                         title: 'Order Rejected!',
        //                         text: 'The order has been successfully rejected.'
        //                     });

        //                     $('#viewModal').modal('hide');
        //                     $('#ordersTable').DataTable().ajax.reload();
        //                 },
        //                 error: function (xhr) {
        //                     Swal.fire({
        //                         icon: 'error',
        //                         title: 'Rejection Failed!',
        //                         text: xhr.responseJSON?.message || 'Something went wrong!'
        //                     });
        //                 }
        //             });
        //         }
        //     });
        // });

        // $(document).on('click', '.view-order', function () {
        //     let orderId = $(this).data('id'); // Get order ID from button

        //     $.ajax({
        //         url: "/accounts/orders/view/" + orderId, // Ensure correct route
        //         type: "GET",
        //         success: function (response) {
        //             if (response.success) {
        //                 let order = response.order;

        //                 // Set Order Details
        //                 $('#view_order_id').text(order.order_id);
        //                 $('#view_date').text(order.date);
        //                 $('#view_employee_type').text(order.employee_type);
        //                 $('#view_employee_name').text(order.employee_name_code);
        //                 $('#view_dealer_name').text(order.dealer_name);
        //                 $('#view_dealer_code').text(order.dealer_code);
        //                 $('#view_dealer_phone').text(order.dealer_phone);
        //                 $('#view_dealer_address').text(order.dealer_address);
        //                 $('#view_order_type').text(order.order_type);
        //                 $('#view_payment_type').text(order.payment_type);
        //                 $('#view_billing_date').text(order.billing_date);
        //                 $('#view_status').html(order.status_badge);

        //                 $('#view_total_outstanding').text(order.total_outstanding);

        //                 let productHtml = '';
        //                 let totalQuantity = 0;
        //                 let totalAmount = 0;

        //                 order.order_items.forEach(item => {
        //                     totalQuantity += item.quantity;
        //                     totalAmount += item.quantity * item.rate;
        //                     productHtml += `
        //                         <tr>
        //                             <td>${item.product_name}</td>
        //                             <td>${item.type_name}</td>
        //                             <td>${item.quantity}</td>
        //                             <td>${(item.quantity * item.rate).toFixed(2)}</td>
        //                         </tr>
        //                     `;
        //                 });

        //                 $('#view_product_list').html(productHtml);
        //                 $('#view_total_quantity').text(totalQuantity);
        //                 $('#view_total_amount').text(totalAmount.toFixed(2));

        //                 // Show Modal
        //                 $('#viewModal').modal('show');
        //             }
        //         }
        //     });
        // });


    });
         
    $(document).on('click', '.view-order', function () {
            let orderId = $(this).data('id');

            $.ajax({
                url: "/accounts/orders/view/" + orderId,
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        let order = response.order;
                        console.log(response.order);
                        // Set Order Details
                        $('#view_order_id').text(order.order_id);
                        $('#view_date').text(order.date);
                        $('#view_employee_type').text(order.employee_type);
                        $('#view_employee_name').text(order.employee_name_code);
                        $('#view_dealer_name').text(order.dealer_name);
                        $('#view_dealer_code').text(order.dealer_code);
                        $('#view_dealer_phone').text(order.dealer_phone);
                        $('#view_dealer_address').text(order.dealer_address);
                        $('#view_order_type').text(order.order_type);
                        $('#view_payment_type').text(order.payment_type);
                        $('#view_billing_date').text(order.billing_date);
                        $('#view_status').html(order.status_badge);
                        $('#order_status').html(order.order_status);
                        $('#view_total_outstanding').text(order.total_outstanding);

                        let productHtml = '';
                        let totalQuantity = 0;
                        let totalAmount = 0;

                        order.order_items.forEach(item => {
                            totalQuantity += item.quantity;
                            totalAmount += item.quantity * item.rate;
                            productHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.type_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>${(item.quantity * item.rate).toFixed(2)}</td>
                                </tr>
                            `;
                        });

                        $('#view_product_list').html(productHtml);
                        $('#view_total_quantity').text(totalQuantity);
                        $('#view_total_amount').text(totalAmount.toFixed(2));

                        // Handle Order Approval Status
                        if (order.order_approved == '1') {
                            $('#payment-form').hide();
                            $('#approval-buttons').hide();
                            $('#payment-details').show();
                            $('#view_payment_term_row').show();
                            $('#view_status_row').show();
                            $('#view_reason_row').hide();
                            $('#view_payment_term').text(order.payment_term);
                            $('#view_remarks').text(order.remarks);

                        } else if (order.order_approved == '2') {
                            $('#payment-form').hide();
                            $('#approval-buttons').hide();
                            $('#payment-details').show();
                            $('#view_payment_term_row').hide();
                            $('#view_status_row').show();
                            $('#view_reason_row').show();

                            $('#view_reason').text(order.reason_for_rejection);

                        } else {
                            $('#payment-form').show();
                            $('#approval-buttons').show();
                            $('#payment-details').hide();

                        }

                        // Store orderId for later use
                        $('#approve_order, #reject_order').data('id', orderId);

                        // Show Modal
                        $('#viewModal').modal('show');
                    }
                }
            });
    });

    $('#approve_order').click(function () {
        let orderId = $(this).data('id');
        let paymentTerm = $('#payment_term').val();
        let remarks = $('#remarks').val();

        if (!paymentTerm) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Term Required!',
                text: 'Please select a payment term before approving the order.',
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure you want to Approve this order?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/accounts/orders/approve/" + orderId,
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        payment_term: paymentTerm,
                        remarks: remarks
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Approved!',
                            text: 'The order has been successfully approved.',
                        });

                        $('#viewModal').modal('hide');
                        $('#ordersTable').DataTable().ajax.reload();
                    }
                });
            }
        });
    });

    $('#reject_order').click(function () {
        let orderId = $('#view_order_id').text();
        let rejectionReason = $('#rejection_reason').val().trim(); 

        if (!rejectionReason) {
            Swal.fire({
                icon: 'warning',
                title: 'Rejection Reason Required!',
                text: 'Please enter a reason before rejecting the order.',
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure you want to reject this order?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/accounts/orders/reject/" + orderId,
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        reason_for_rejection: rejectionReason
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Rejected!',
                            text: 'The order has been successfully rejected.',
                        });

                        $('#viewModal').modal('hide');
                        $('#ordersTable').DataTable().ajax.reload();
                    },
                    error: function (xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong while rejecting the order.',
                        });
                    }
                });
            }
        });
    });
</script>
@endsection
