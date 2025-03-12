<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> --}}
  {{-- <script src="{{ asset('js/vendor/popper.min.js') }}"></script> --}}
  <!-- Add Popper.js Before Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    {{-- <script src="{{ asset('js/bootstrap.min.js') }}"></script> --}}

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.3/dist/alpine.min.js" defer></script>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

@yield('scripts')

<script>

// $(document).ready(function () {

//     $(".menu-title").click(function () {
//         var $submenu = $(this).next(".submenu"); // Target the next UL (submenu)
        
//         if ($submenu.is(":visible")) {
//             $submenu.slideUp(); // Hide submenu
//             $(this).find(".icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
//         } else {
//             $(".submenu").slideUp(); // Close all other open menus
//             $(".menu-title .icon-right i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
            
//             $submenu.slideDown(); // Show clicked submenu
//             $(this).find(".icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
//         }
//     });

//     $(".submenu a.active").each(function () {
//         $(this).closest(".submenu").slideDown();
//         $(this).closest("li").find(".menu-title .icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
//     });

// });
$(document).ready(function () {
    let currentUrl = window.location.href;

    // Loop through each submenu item to check if it matches the current page URL
    $(".submenu a").each(function () {
        if (this.href === currentUrl) {
            $(this).addClass("active"); // Highlight active submenu link
            $(this).closest(".submenu").slideDown(); // Keep submenu open
            $(this).closest("li").find(".menu-title").addClass("active"); // Highlight main menu
            $(this).closest("li").find(".menu-title .icon-right i")
                .removeClass("fa-angle-down")
                .addClass("fa-angle-up"); // Change icon to up arrow
        }
    });

    // Highlight the dashboard if it's active
    $(".dashboard").each(function () {
        if (this.href === currentUrl) {
            $(this).addClass("active");
        }
    });

    // Toggle submenus on menu-title click
    $(".menu-title").click(function () {
        var $submenu = $(this).next(".submenu");

        if ($submenu.is(":visible")) {
            $submenu.slideUp(); // Hide submenu
            $(this).removeClass("active"); // Remove active class
            $(this).find(".icon-right i").removeClass("fa-angle-up").addClass("fa-angle-down");
        } else {
            $(".submenu").slideUp(); // Close all other open menus
            $(".menu-title").removeClass("active"); // Remove active from all menu titles
            $(".menu-title .icon-right i").removeClass("fa-angle-up").addClass("fa-angle-down");

            $submenu.slideDown(); // Show clicked submenu
            $(this).addClass("active"); // Set active class
            $(this).find(".icon-right i").removeClass("fa-angle-down").addClass("fa-angle-up");
        }
    });
});

</script>
</body>
</html>