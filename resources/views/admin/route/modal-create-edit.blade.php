<div class="modal fade" id="createEditRouteModal" tabindex="-1" aria-labelledby="createEditRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditRouteModalLabel">Create Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="routeForm">
                @csrf
                <div class="modal-body">
                   
                    <div class="row">
                        <input type="text" name="id" id="route_id">
                    
                        <!-- District Selection -->
                        <div class="col-md-6">
                            <label>District</label>
                            <select class="form-control" name="district_id" id="district_id">
                                <option value="">-Select District-</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Route Name -->
                        <div class="col-md-6">
                            <label>Route Name</label>
                            <input type="text" class="form-control" name="route_name" id="route_name" required>
                        </div>

                        <!-- Location Name -->
                        <div class="col-md-12">
                            <label>Location Name</label>
                            <input type="text" class="form-control" name="location_name" id="location_name" required>
                        </div>

                        <!-- Sub Locations (Comma-separated) -->
                        <div class="col-md-12">
                            <label>Sub Locations (comma-separated)</label>
                            <input class="form-control" id="sub-locations" name="sub_locations" type="text" placeholder="Enter sub-locations" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Route</button>
                </div>
            </form>
        </div>
    </div>
</div>
