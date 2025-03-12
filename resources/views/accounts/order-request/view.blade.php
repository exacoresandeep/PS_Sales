<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Order ID:</strong> <span id="view_order_id"></span></div>
                        <div class="col-md-6"><strong>Date:</strong> <span id="view_date"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Employee Type:</strong> <span id="view_employee_type"></span></div>
                        <div class="col-md-6"><strong>Employee Name:</strong> <span id="view_employee_name"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Dealer Name:</strong> <span id="view_dealer_name"></span></div>
                        <div class="col-md-6"><strong>Dealer Code:</strong> <span id="view_dealer_code"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Phone:</strong> <span id="view_dealer_phone"></span></div>
                        <div class="col-md-6"><strong>Address:</strong> <span id="view_dealer_address"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Order Type:</strong> <span id="view_order_type"></span></div>
                        <div class="col-md-6"><strong>Payment Type:</strong> <span id="view_payment_type"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Billing Date:</strong> <span id="view_billing_date"></span></div>
                        <div class="col-md-6"><strong>Status:</strong> <span id="view_status"></span></div>
                    </div>

                    <h5 class="mt-3">Product Details</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="view_product_list"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th id="view_total_quantity"></th>
                                <th id="view_total_amount"></th>
                            </tr>
                        </tfoot>
                    </table>


                     <h5 class="mt-3">Credit Details</h5>
                     <div class="alert alert-danger">
                         <strong>Total Outstanding Amount:</strong> <span id="view_total_outstanding"></span>
                     </div>
                    
                    <h5 class="mt-3">Approval Section</h5>
                    <div class="row" id="payment-form">
                        <div class="col-md-6">
                            <label for="payment_term" class="form-label">Payment Term</label>
                            <select id="payment_term" class="form-control">
                                <option value="">Select Payment Term</option>
                                <option value="Cash Basic">Cash Basic</option>
                                <option value="Net-04">Net-04</option>
                                <option value="Net-30">Net-30</option>
                                <option value="Net-12">Net-12</option>
                                <option value="Net-15">Net-15</option>
                                <option value="Advance">Advance</option>
                                <option value="Credit DP">Credit DP</option>
                                <option value="DP (-5000)">DP (-5000)</option>
                                <option value="Net-25">Net-25</option>
                                <option value="Net-03">Net-03</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea id="remarks" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="rejection_reason" class="form-label">Rejection Reason </label>
                            <textarea id="rejection_reason" class="form-control" rows="3" placeholder="Enter rejection reason"></textarea>
                            <span id="rejection_error" class="text-danger" style="display: none;">Rejection reason is required.</span>
                        </div>
                    </div>
                  
                    
                  
                    <div id="payment-details" style="display: none;">
                        
                        <div class="row mb-2" id="view_payment_term_row">
                            <div class="col-md-6"><strong>Payment Term:</strong> <span id="view_payment_term"></span></div>
                            <div class="col-md-6"><strong>Remarks:</strong> <span id="view_remarks"></span></div>
                        </div>
                       
                        <div class="row mb-2" id="view_status_row">
                            <div class="col-md-6"><strong>Status:</strong> <span id="order_status"></span></div>
                            <div class="col-md-6" id="view_reason_row" style="display: none;"><strong>Rejection Reason:</strong> <span id="view_reason"></span></div>
                        </div>
                        <div class="row mb-2" >
                        </div>
                    </div>

                    <div class="mt-3 text-end" id="approval-buttons">
                        <button type="button" class="btn btn-danger" id="reject_order">Reject</button>
                        <button type="button" class="btn btn-success" id="approve_order">Approve</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
