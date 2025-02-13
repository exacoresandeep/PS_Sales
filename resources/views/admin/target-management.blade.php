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
            <th>Target By Year</th>
            <th>Target By Month</th>
            <th>Unique Lead</th>
            <th>Customer Visit</th>
            <th>Aashiyana Count</th>                        
            <th>Targets in Tons</th>
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
              <form id="targetForm" action="{{ route('targets.store') }}" method="POST">
                  @csrf
                  <div class="row">
                      <!-- Employee Type -->
                      <div class="col-md-6">
                          <label for="employee_type" class="form-label">Employee Type</label>
                          <select class="form-control" name="employee_type" id="employee_type" required>
                              <option value="">Select Employee Type</option>
                              @foreach($employeeTypes as $type)
                                  <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                              @endforeach
                          </select>
                      </div>

                      <!-- Employee Name -->
                      <div class="col-md-6">
                          <label for="employee_id" class="form-label">Employee Name</label>
                          <select class="form-control" name="employee_id" id="employee_id" required>
                              <option value="">Select Employee</option>
                              @foreach($employees as $employee)
                                  <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                              @endforeach
                          </select>
                      </div>

                      <!-- Target Year -->
                      <div class="col-md-6 mt-3">
                          <label for="year" class="form-label">Target By Year</label>
                          <select class="form-control" name="year" id="year" required>
                              @php
                                  $currentYear = date('Y');
                                  for ($i = 0; $i < 5; $i++) {
                                      echo '<option value="' . ($currentYear + $i) . '">' . ($currentYear + $i) . '</option>';
                                  }
                              @endphp
                          </select>
                      </div>

                      <!-- Target Month -->
                      <div class="col-md-6 mt-3">
                          <label for="month" class="form-label">Target By Month</label>
                          <select class="form-control" name="month" id="month" required>
                              <option value="January">January</option>
                              <option value="February">February</option>
                              <option value="March">March</option>
                              <option value="April">April</option>
                              <option value="May">May</option>
                              <option value="June">June</option>
                              <option value="July">July</option>
                              <option value="August">August</option>
                              <option value="September">September</option>
                              <option value="October">October</option>
                              <option value="November">November</option>
                              <option value="December">December</option>
                          </select>
                      </div>

                      <!-- Unique Lead -->
                      <div class="col-md-6 mt-3">
                          <label for="unique_lead" class="form-label">Unique Lead</label>
                          <input type="number" class="form-control" name="unique_lead" id="unique_lead" required>
                      </div>

                      <!-- Customer Visit -->
                      <div class="col-md-6 mt-3">
                          <label for="customer_visit" class="form-label">Customer Visit</label>
                          <input type="number" class="form-control" name="customer_visit" id="customer_visit" required>
                      </div>

                      <!-- Aashiyana Count -->
                      <div class="col-md-6 mt-3">
                          <label for="aashiyana" class="form-label">Aashiyana Count</label>
                          <input type="number" class="form-control" name="aashiyana" id="aashiyana" required>
                      </div>

                      <!-- Target By Ton -->
                      <div class="col-md-6 mt-3">
                          <label for="order_quantity" class="form-label">Target By Ton</label>
                          <input type="number" class="form-control" name="order_quantity" id="order_quantity" required>
                      </div>
                  </div>
                  
                  <div class="modal-footer mt-4">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                { data: 'year', name: 'year' },
                { data: 'month', name: 'month' },
                { data: 'unique_lead', name: 'unique_lead' },
                { data: 'customer_visit', name: 'customer_visit' },
                { data: 'aashiyana', name: 'aashiyana' },
                { data: 'order_quantity', name: 'order_quantity' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
        $('#employee_type').on('change', function () {
            let employeeTypeId = $(this).val();
            $('#employee_id').html('<option value="">Loading...</option>'); // Show loading

            if (employeeTypeId) {
                $.ajax({
                    url: "{{ url('/get-employees') }}/" + employeeTypeId,
                    type: "GET",
                    success: function (response) {
                        $('#employee_id').html('<option value="">Select Employee</option>'); // Reset dropdown
                        $.each(response, function (index, employee) {
                            $('#employee_id').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                        });
                    },
                    error: function () {
                        $('#employee_id').html('<option value="">Error loading employees</option>');
                    }
                });
            } else {
                $('#employee_id').html('<option value="">Select Employee</option>');
            }
        });
        $('#targetForm').submit(function (e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('targets.store') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    alert("Target created successfully!");
                    $('#createTargetModal').modal('hide');
                    $('#example1').DataTable().ajax.reload();
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
