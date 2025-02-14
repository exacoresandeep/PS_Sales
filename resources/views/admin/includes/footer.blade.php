{{-- <footer class="text-center mt-4">
    <p>&copy; {{ date('Y') }} Prabhu Steels. All rights reserved.</p>
</footer> --}}
  <!-- Bootstrap core JavaScript -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="{{ asset('js/vendor/popper.min.js') }}"></script>
  <script src="{{ asset('js/bootstrap.min.js') }}"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS (Load after jQuery) -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    // Ensure that active menu is expanded on page load
    $(".submenu a.active").each(function () {
        $(this).closest(".submenu").slideDown();
        $(this).closest("li").find(".menu-title .icon-right i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
    });
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