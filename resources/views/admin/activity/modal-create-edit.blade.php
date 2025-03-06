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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="district">District</label>
                            <select class="form-control" id="district" name="district" required>
                                <option value="">-Select District-</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dealer_id">Dealer</label>
                            <select class="form-control" id="dealer_id" name="dealer_id" required>
                                <option value="">-Select Dealer-</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
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
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="assigned_date">Assigned Date</label>
                            <input type="date" class="form-control" id="assigned_date" name="assigned_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="instruction">Instruction</label>
                            <textarea class="form-control" id="instruction" name="instruction" rows="3" required></textarea>
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


