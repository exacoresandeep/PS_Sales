<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Target Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <table class="table table-bordered">
                    <tr>
                        <th>Employee Type:</th>
                        <td>{{ $target->employee->employeeType->type_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Employee Name:</th>
                        <td>{{ $target->employee->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Year:</th>
                        <td>{{ $target->year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Month:</th>
                        <td>{{ $target->month ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Unique Lead:</th>
                        <td>{{ $target->unique_lead ?? '0' }}</td>
                    </tr>
                    <tr>
                        <th>Customer Visit:</th>
                        <td>{{ $target->customer_visit ?? '0' }}</td>
                    </tr>
                    <tr>
                        <th>Aashiyana Count:</th>
                        <td>{{ $target->aashiyana ?? '0' }}</td>
                    </tr>
                    <tr>
                        <th>Targets in Tons:</th>
                        <td>{{ $target->order_quantity ?? '0' }}</td>
                    </tr>
                </table>
                
            </div>
        </div>
    </div>
</div>
