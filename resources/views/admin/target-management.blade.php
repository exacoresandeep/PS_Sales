<div class="activity-sec">
    <div class="inner-header button-align">
      <h3>Target Management</h3>
      {{-- <button class="btn btn-primary">Create Target</button> --}}
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTargetModal">
        Create Target
      </button>
    </div>                
    <div class="filter-sec">
      <div class="row">                      
        <div class="col-md-2">
          <label>Employee Type</label>
          <select class="form-control">
            <option>-Select Usertype-</option>
          </select>
        </div>
        <div class="col-md-2">
          <label>Employee</label>
          <select class="form-control">
            <option>-Select Employee-</option>
          </select>
        </div>  
        <div class="col-md-2">
          <label>Target Status</label>
          <select class="form-control">
            <option>-Select Status-</option>
          </select>
        </div>                    
        <div class="col-md-2">
          <label>Start Date</label>
          <input type="text" class="form-control" name="" placeholder="Start Date">
        </div>
        <div class="col-md-2">
          <label>End Date</label>
          <input type="text" class="form-control" name="" placeholder="End Date">
        </div>
        <div class="col-md-2 search-btn">                      
          <input type="submit" class="btn btn-primary" name="" value="Search">
        </div>
      </div>
    </div>
    
    <div class="listing-sec">
      <table class="table table-bordered table-responsive table-striped w-100" id="example1" >
          <thead>
          <tr>
            <th>Sl.No</th>
            <th>Employee Type</th>
            <th>Employee Name</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Targets in Tons</th>
            <th>Target in Number</th>                        
            <th>Action</th>
          </tr>
          </thead>
          <tbody>
             
          </tbody>
         
        </table>
    </div>
  </div>
  <div class="modal fade" id="createTargetModal" tabindex="-1" aria-labelledby="createTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Extra Large Modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTargetModalLabel">Create Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> <!-- Fixed Close Button -->
            </div>
            <div class="modal-body">
                <form id="createTargetForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="month" class="form-label">Month</label>
                            <input type="text" class="form-control" id="month" name="month" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="text" class="form-control" id="year" name="year" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="unique_lead" class="form-label">Unique Lead</label>
                            <input type="number" class="form-control" id="unique_lead" name="unique_lead">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer_visit" class="form-label">Customer Visit</label>
                            <input type="number" class="form-control" id="customer_visit" name="customer_visit">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="activity_visit" class="form-label">Activity Visit</label>
                            <input type="number" class="form-control" id="activity_visit" name="activity_visit">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="aashiyana" class="form-label">Aashiyana</label>
                            <input type="number" class="form-control" id="aashiyana" name="aashiyana">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="order_quantity" class="form-label">Order Quantity</label>
                            <input type="number" class="form-control" id="order_quantity" name="order_quantity">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Target</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

  <div class="modal fade" id="viewEditModal" tabindex="-1" aria-labelledby="viewEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEditModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <!-- Dynamic content loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveChangesButton" style="display: none;">Save Changes</button>
            </div>
        </div>
    </div>
  </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


  <script>
      $(document).ready(function(){
          $('#viewEditModal').on('show.bs.modal', function () {
              $(this).attr('aria-hidden', 'false');
          });
          $('.close').click(function() { 
              $(this).closest('.modal').modal('hide');
          });
          var table = $('#example1').DataTable({
              processing: true,
              serverSide: true,
              ajax: {
                  url: "{{ url('/admin/targetList') }}", 
                  type: 'POST',
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                  }
              },
              columns: [
                  { 
                      data: null, 
                      name: 'sl_no',
                      render: function (data, type, row, meta) {
                          return meta.row + meta.settings._iDisplayStart + 1; 
                      },
                      orderable: false, 
                      searchable: false,
                      class:"text-center"
                  },
                  { data: 'employee_type', name: 'employee_type' },
                  { data: 'employee_name', name: 'employee_name' },
                  { data: 'from_date', name: 'from_date' },
                  { data: 'to_date', name: 'to_date' },
                  { data: 'target_tons', name: 'target_tons' },
                  { data: 'target_numbers', name: 'target_numbers' },
                  { data: 'action', name: 'action', orderable: false, searchable: false }
              ]
          });
          $('#createTargetForm').submit(function (e) {
              e.preventDefault();

              $.ajax({
                  url: "{{ route('targets.store') }}", 
                  type: "POST",
                  data: $(this).serialize(),
                  success: function (response) {
                      alert("Target created successfully!");
                      $('#createTargetModal').modal('hide');
                      location.reload();
                  },
                  error: function (xhr) {
                      alert("Something went wrong!");
                      console.log(xhr.responseText);
                  }
              });
          });
  
      });
      function handleAction(itemId, action) {
          $.ajax({
              url: '/admin/viewTarget/' + itemId,
              method: 'GET',
              success: function(response) {
                  if (action === 'view') {
                      $('#viewEditModalLabel').text('View Target Details');
                      $('#modalBodyContent').html(response.data);
                      $('#viewEditModal').modal('show');
                  }
              },
              error: function() {
                  Swal.fire('Error', 'Could not fetch Target details.', 'error');
              }
          });
      }
      function deleteTarget(itemId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/deleteTarget/' + itemId,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Deleted!', response.message, 'success');
                        $('#example1').DataTable().ajax.reload();
                    },
                    error: function() {
                        Swal.fire('Error', 'Could not delete Target.', 'error');
                    }
                });
            }
        });
    }
  </script>
