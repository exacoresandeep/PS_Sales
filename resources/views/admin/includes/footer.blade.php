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

<script>

$(document).ready(function () {
    function loadViewModal(id) {
        $.get("{{ route('admin.target.get', '') }}/" + id, function (response) {
            $('#viewModalBody').html(response.viewContent);
            $('#viewModal').modal('show');
        }).fail(function () {
            Swal.fire('Error', 'Could not load details.', 'error');
        });
    }
    $('#openCreateModal').click(function () {
        $('#targetForm')[0].reset(); 
        $('#target_id').val(''); 
        $('#createEditModalLabel').text('Create Target');
        $('#createEditModal').modal('show'); 
    });
    $('#employee_type').change(function () {
        let employeeTypeId = $(this).val();
        $('#employee_id').html('<option value="">Loading...</option>');

        if (employeeTypeId) {
            $.get("{{ route('admin.getEmployees', '') }}/" + employeeTypeId, function (response) {
                $('#employee_id').html('<option value="">-Select Employee-</option>');
                $.each(response, function (index, employee) {
                    $('#employee_id').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                });
            }).fail(function () {
                Swal.fire('Error', 'Could not load employees.', 'error');
            });
        } else {
            $('#employee_id').html('<option value="">-Select Employee-</option>');
        }
    });

    // Handle Target Form Submission
    $('#targetForm').submit(function (e) {
        e.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('admin.target.store') }}", 
            type: "POST",
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Swal.fire('Success', response.message, 'success');
                $('#createEditModal').modal('hide'); // Close modal
                $('#targetTable').DataTable().ajax.reload(); // Reload DataTable
            },
            error: function (xhr) {
                Swal.fire('Error', 'Could not save target.', 'error');
            }
        });
    });
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

    // Ensure that active menu is expanded on page load
    $(".submenu a.active").each(function () {
        $(this).closest(".submenu").slideDown();
        $(this).closest("li").find(".menu-title .icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
    });
    var table = $('#targetTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.target.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.employee_type = $('#filter_employee_type').val();
                d.employee_id = $('#filter_employee').val();
                d.year = $('#filter_year').val();
                d.month = $('#filter_month').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
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

    // Reload DataTable when filters change
    $('.filter-sec select').change(function () {
        table.ajax.reload();
    });

    // Load Employees based on Employee Type
    $('#filter_employee_type').change(function () {
        let employeeTypeId = $(this).val();
        $('#filter_employee').html('<option value="">Loading...</option>');

        if (employeeTypeId) {
            $.get("{{ route('admin.getEmployees', '') }}/" + employeeTypeId, function (response) {
                $('#filter_employee').html('<option value="">-Select Employee-</option>');
                $.each(response, function (index, employee) {
                    $('#filter_employee').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                });
            });
        } else {
            $('#filter_employee').html('<option value="">-Select Employee-</option>');
        }
    });
    
    function deleteTarget(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('admin.target.delete', '') }}/" + id,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            $('#targetTable').DataTable().ajax.reload(); // Reload DataTable
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not delete target.', 'error');
                    }
                });
            }
        });
    }


    // Delete Target
    // function deleteTarget(id) {
    //     Swal.fire({
    //         title: 'Are you sure?',
    //         text: "This action cannot be undone!",
    //         icon: 'warning',
    //         showCancelButton: true,
    //         confirmButtonText: 'Yes, delete it!',
    //         cancelButtonText: 'No, cancel!'
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             $.post("{{ route('admin.target.delete', '') }}/" + id, {
    //                 _token: $('meta[name="csrf-token"]').attr('content')
    //             }, function (response) {
    //                 Swal.fire('Deleted!', response.message, 'success');
    //                 table.ajax.reload();
    //             }).fail(function () {
    //                 Swal.fire('Error', 'Could not delete target.', 'error');
    //             });
    //         }
    //     });
    // }

    // Global function for edit/view actions
    window.handleAction = function (id, action) {
        $.get("{{ route('admin.target.get', '') }}/" + id, function (response) {
            if (action === 'edit') {
                $('#createEditModalLabel').text('Edit Target');
                $('#createEditModal input, #createEditModal select').each(function () {
                    $(this).val(response[$(this).attr('name')]);
                });
                $('#createEditModal').modal('show');
            } else if (action === 'view') {
                $('#viewModalLabel').text('Target Details');
                $('#viewModalBody').html(response.viewContent);
                $('#viewModal').modal('show');
            }
        }).fail(function () {
            Swal.fire('Error', 'Could not fetch target details.', 'error');
        });
    };

    // Bind Delete Function
    window.deleteTarget = deleteTarget;
});



//  $(document).ready(function () {

    
//     function getCookie(name) {
//         let cookies = document.cookie.split("; ");
//         for (let i = 0; i < cookies.length; i++) {
//             let cookie = cookies[i].split("=");
//             if (cookie[0] === name) {
//                 return decodeURIComponent(cookie[1]);
//             }
//         }
//         return null;
//     }

//     function setCookie(name, value) {
//         document.cookie = name + "=" + encodeURIComponent(value) + "; path=/";
//     }

//     function loadContent(link) {
      
//         $.ajax({
//             url: '/load-content/' + link,
//             // url: link,
//             type: "GET",
//             success: function (response) {
//                 $(".dashboard-area").html(response);
//             },
//             error: function () {
//                 $(".dashboard-area").html("<p>Error loading content.</p>");
//             }
//         });
//     }

//     // Restore previously selected link
//     var selectedLink = getCookie("selectedLink");
//     if (selectedLink) {
//         $(".menu-title, .submenu a").removeClass("active");
//         var linkElement = $('.submenu a[href="' + selectedLink + '"]');
//         if (linkElement.length) {
//             linkElement.addClass("active");
//             var parentMenu = linkElement.closest("ul");
//             if (parentMenu.length) {
//                 var parentMenuLi = parentMenu.closest("li");
//                 parentMenuLi.addClass("menu-open menu-is-opening");
//                 parentMenu.slideDown();
//             }
//         }
//         loadContent(selectedLink);
//     } else {
//         loadContent("dashboard");
//     }

//     $(".menu-title").click(function () {
//         var $submenu = $(this).next("ul"); // Target only the next UL (submenu)
        
//         if ($submenu.is(":visible")) {
//             $submenu.slideUp();
//             $(this).find(".icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
//         } else {
//             $(".submenu").slideUp(); // Close all other open menus
//             $(".menu-title .icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
            
//             $submenu.slideDown();
//             $(this).find(".icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
//         }
//     });

//     $(".submenu a").on("click", function (event) {
//         event.preventDefault();

//         $(".submenu a").removeClass("active");
//         var link = $(this).attr("href");

//         if (link !== "#") {
//             setCookie("selectedLink", link);
//             $(this).addClass("active");
//             loadContent(link);
//         }
//     });


    
// });


</script>
</body>
</html>