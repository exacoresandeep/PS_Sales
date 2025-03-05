<div class="modal fade" id="createEditActivityModal" tabindex="-1" aria-labelledby="createEditActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditActivityModalLabel">Create Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityForm">
                @csrf
                <input type="hidden" id="activity_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="district">District</label>
                            <select class="form-control" id="district" name="district" required>
                                <option value="">-Select District-</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->district }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dealer_id">Dealer</label>
                            <select class="form-control" id="dealer_id" name="dealer_id" required>
                                <option value="">-Select Dealer-</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="employee_id">Assigned Employee</label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">-Select Employee-</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="activity_type_id">Activity Type</label>
                            <select class="form-control" id="activity_type_id" name="activity_type_id" required>
                                <option value="">-Select Activity Type-</option>
                                @foreach($activityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="assigned_date">Assigned Date</label>
                            <input type="date" class="form-control" id="assigned_date" name="assigned_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    $(document).ready(function () {

        // When district is changed, load dealers & employees (only SEs) for that district
        $('#district').change(function () {
            let district_id = $(this).val();
            
            if (district_id) {
                // Fetch dealers in the selected district
                $.ajax({
                    url: `/admin/dealers-by-district/${district_id}`,
                    type: 'GET',
                    success: function (response) {
                        $('#dealer_id').html('<option value="">-Select Dealer-</option>');
                        $.each(response, function (key, dealer) {
                            $('#dealer_id').append(`<option value="${dealer.id}">${dealer.dealer_name} (${dealer.dealer_code})</option>`);
                        });
                    }
                });

                // Fetch Sales Executives (employee_type_id = 1) in the selected district
                $.ajax({
                    url: `/admin/employees-by-district/${district_id}`,
                    type: 'GET',
                    success: function (response) {
                        $('#employee_id').html('<option value="">-Select Employee-</option>');
                        $.each(response, function (key, employee) {
                            $('#employee_id').append(`<option value="${employee.id}">${employee.name}</option>`);
                        });
                    }
                });
            }
        });

        // Open create modal
        $('#openCreateActivityModal').click(function () {
            alert('dsf00');
            $('#activityForm')[0].reset();
            $('#activity_id').val('');
            $('#createEditActivityModalLabel').text('Create Activity');
            $('#createEditActivityModal').modal('show');
        });

        // Handle form submission (Create & Update)
        $('#activityForm').submit(function (e) {
            e.preventDefault();
            let id = $('#activity_id').val();
            let url = id ? `/admin/activity/update/${id}` : "/admin/activity/store";
            let method = id ? 'PUT' : 'POST';
            
            let formData = $(this).serialize();
            if (id) {
                formData += '&_method=PUT';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#createEditActivityModal').modal('hide');
                    $('#activityForm')[0].reset();
                    $('#activityTable').DataTable().ajax.reload();
                },
                error: function (xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        });

        // Open edit modal and populate data
        $(document).on('click', '.editActivity', function () {
            let id = $(this).data('id');
            $.ajax({
                url: `/admin/activity/edit/${id}`,
                type: 'GET',
                success: function (response) {
                    $('#activity_id').val(response.activity.id);
                    $('#activity_type_id').val(response.activity.activity_type_id);
                    $('#district').val(response.activity.district_id).change();

                    setTimeout(() => {
                        $('#dealer_id').val(response.activity.dealer_id);
                        $('#employee_id').val(response.activity.employee_id);
                    }, 1000);

                    $('#assigned_date').val(response.activity.assigned_date);
                    $('#due_date').val(response.activity.due_date);
                    $('#status').val(response.activity.status);
                    $('#createEditActivityModalLabel').text('Edit Activity');
                    $('#createEditActivityModal').modal('show');
                },
                error: function () {
                    alert('Error fetching activity details.');
                }
            });
        });

    });
</script>
@endsection
