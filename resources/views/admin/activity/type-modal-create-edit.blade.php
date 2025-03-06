<div class="modal fade" id="createEditActivityTypeModal" tabindex="-1" aria-labelledby="createEditActivityTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditActivityTypeModalLabel">Create Activity Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityTypeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="activity_type_id">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label>Activity Name</label>
                            <input type="text" class="form-control" name="activity_name" id="activity_name" required>
                        </div>

                        <div class="col-md-12">
                            <label>Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> --}}
                    <button type="submit" class="btn btn-primary submit-btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
