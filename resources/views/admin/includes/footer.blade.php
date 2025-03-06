{{-- <footer class="text-center mt-4">
    <p>&copy; {{ date('Y') }} Prabhu Steels. All rights reserved.</p>
</footer> --}}
  <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> --}}
  {{-- <script src="{{ asset('js/vendor/popper.min.js') }}"></script> --}}
  <!-- Add Popper.js Before Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>

<!-- Bootstrap (If Used) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- <script src="{{ asset('js/bootstrap.min.js') }}"></script> --}}


<!-- Bootstrap JS (Load after jQuery) -->

<!-- Bootstrap 5 JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.3/dist/alpine.min.js" defer></script>

    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    @yield('scripts')
<script>

$(document).ready(function () {

    $(".menu-title").click(function () {
        var $submenu = $(this).next(".submenu"); // Target the next UL (submenu)
        
        if ($submenu.is(":visible")) {
            $submenu.slideUp(); // Hide submenu
            $(this).find(".icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
        } else {
            $(".submenu").slideUp(); // Close all other open menus
            $(".menu-title .icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
            
            $submenu.slideDown(); // Show clicked submenu
            $(this).find(".icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
        }
    });

    $(".submenu a.active").each(function () {
        $(this).closest(".submenu").slideDown();
        $(this).closest("li").find(".menu-title .icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
    });

 
    // Routes
    // $('#openCreateRouteModal').click(function () {
    //     $('#routeForm')[0].reset(); 
    //     $('#route_id').val(''); 
    //     $('#createEditRouteModalLabel').text('Create Target');
    //     $('#createEditRouteModal').modal('show'); 
    // });

    // let subLocationInput;
    // $('#createEditRouteModal').on('shown.bs.modal', function () {
    //     if (!subLocationInput) {
    //         subLocationInput = new Choices('#sub-locations', {
    //             delimiter: ',',
    //             editItems: true,
    //             removeItemButton: true,
    //             paste: false,
    //             duplicateItemsAllowed: false,
    //             placeholderValue: 'Enter sub-locations',
    //             searchPlaceholderValue: 'Search sub-location'
    //         });
    //     }
    // });



    // $('#createEditRouteModal').on('hidden.bs.modal', function () {
    //     subLocationInput.clearStore();  
    //     subLocationInput.clearInput();  
    // });

    // $('#routeForm').submit(function (e) {
        
    //     e.preventDefault();
    //     let subLocationsArray = subLocationInput.getValue(true);
    //     $('#sub-locations').val(JSON.stringify(subLocationsArray)); // Convert array to JSON string

    //     // Serialize the form data
    //     let formData = $(this).serialize();
    //     let routeId = $('#route_id').val();
    //     let url = routeId ? "{{ route('admin.route.update') }}" : "{{ route('admin.route.store') }}";
        
    //     $.ajax({
    //         url: url,
    //         type: "POST",
    //         data: formData,
    //         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    //         success: function (response) {
    //             Swal.fire('Success', response.message, 'success');
    //             $('#createEditRouteModal').modal('hide');  // Hide the modal
    //             $('#routeTable').DataTable().ajax.reload();  // Reload the route table

    //             // Reset the sub-location input after saving
    //             subLocationInput.clearStore();
    //             subLocationInput.clearInput();
    //         },
    //         error: function (xhr) {
    //             Swal.fire('Error', 'Could not save route.', 'error');
    //         }
    //     });
    // });

    // var table = $('#routeTable').DataTable({
    //     processing: true,
    //     serverSide: true,
    //     searching: true,
    //     ajax: {
    //         url: "{{ route('admin.route.list') }}",
    //         type: 'POST',
    //         headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    //         data: function (d) {
    //             d.district_id = $('#filter_district').val();
    //             d.route_name = $('#filter_route').val();
    //         }
    //     },
    //     columns: [
    //         { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    //         { data: 'district_name', name: 'district_name' }, 
    //         { data: 'route_name', name: 'route_name' },
    //         { data: 'location_name', name: 'location_name' },
    //         { data: 'sub_locations', name: 'sub_locations' },
    //         { data: 'action', name: 'action', orderable: false, searchable: false }
    //     ]
    // });

    // $('#filter_district').change(function () {
    //     let districtId = $(this).val();
    //     $('#filter_route').html('<option value="">Loading...</option>');

    //     if (districtId) {
    //         $.get("{{ route('admin.route.getAllRoutesByDistrict', '') }}/" + districtId, function (response) {
    //             $('#filter_route').html('<option value="">-Select Route-</option>');
    //             $.each(response, function (index, route) {
    //                 $('#filter_route').append('<option value="' + route.id + '">' + route.route_name + '</option>');
    //             });
    //         });
    //     } else {
    //         $('#filter_route').html('<option value="">-Select Route-</option>');
    //     }
    // });



});
</script>
</body>
</html>