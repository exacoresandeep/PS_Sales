<div class="modal fade" id="createEditAssignRouteModal" tabindex="-1" aria-labelledby="createEditAssignedRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Assigned Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="assignedRouteForm">
                @csrf
                <input type="hidden" name="id" id="route_id">

                <div class="modal-body">
                    <!-- First Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <label for="district" class="form-label">District</label>
                            <select id="district" name="district_id" class="form-control " required>
                                <option value="">Select District</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="employee_type" class="form-label">Employee Type</label>
                            <select id="employee_type" name="employee_type_id" class="form-control " required>
                                <option value="">Select Employee Type</option>
                                <option value="1">SE (Sales Executive)</option>
                                <option value="2">ASO (Area Sales Officer)</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="employee" class="form-label">Employee</label>
                            <select id="employee" name="employee_id" class="form-control " required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>

                        <div class="col-md-3" id="asoField" style="display: none;">
                            <label for="aso" class="form-label">Area Sales Officer</label>
                            <select id="aso" name="aso_id" class="form-control " >
                                <option value="">Select Area Sales Officer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Routes & Locations -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Assign Routes & Locations</label>
                        </div>
                    </div>

                    @foreach(range(1, 6) as $i)
                    <div class="row mt-2">
                        <div class="col-md-5">
                            <select name="routes[{{ $i }}][route_name]" class="form-control ">
                                <option value="">Select Route</option>
                                <option value="R1">R1</option>
                                <option value="R2">R2</option>
                                <option value="R3">R3</option>
                                <option value="R4">R4</option>
                                <option value="R5">R5</option>
                                <option value="R6">R6</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <select class="form-control select2-multi" name="routes[{{ $i }}][locations][]" multiple="multiple"></select>
                        </div>
                    </div>
                    @endforeach

                    <!-- Submit Button -->
                    <div class="modal-footer mt-3">
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
