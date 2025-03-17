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

$(document).ready(function () {
    let currentUrl = window.location.href;
    let activeMenu = localStorage.getItem("activeMenu");

    $(".menu-title, .submenu a").removeClass("active");

    let isSubmenuActive = false;

    $(".submenu a").each(function () {
        if (this.href === currentUrl) {
            $(this).addClass("active");
            let $menuTitle = $(this).closest(".submenu").prev(".menu-title");
            $menuTitle.addClass("active");
            $(this).closest(".submenu").slideDown();
            $menuTitle.find(".icon-right i").removeClass("fa-angle-down").addClass("fa-angle-up");

            localStorage.setItem("activeMenu", $menuTitle.text().trim());
            isSubmenuActive = true;
        }
    });

    if (!isSubmenuActive) {
        $(".menu-title").each(function () {
            if (this.href && this.href === currentUrl) {
                $(this).addClass("active");
                isSubmenuActive = true;
            }
        });
    }

    if (!isSubmenuActive && currentUrl.includes("dashboard")) {
        $(".menu-title").first().addClass("active");
    }

    $(".menu-title").click(function () {
        let $submenu = $(this).next(".submenu");

        if ($submenu.length) {
            if ($submenu.is(":visible")) {
                $submenu.slideUp();
                $(this).removeClass("active");
                $(this).find(".icon-right i").removeClass("fa-angle-up").addClass("fa-angle-down");
            } else {
                $(".submenu").slideUp();
                $(".menu-title").removeClass("active");
                $(".menu-title .icon-right i").removeClass("fa-angle-up").addClass("fa-angle-down");

                $submenu.slideDown();
                $(this).addClass("active");
                $(this).find(".icon-right i").removeClass("fa-angle-down").addClass("fa-angle-up");
            }
        } else {
            $(".menu-title").removeClass("active");
            $(this).addClass("active");

            localStorage.setItem("activeMenu", $(this).text().trim());
        }
    });

    $(".submenu a").click(function () {
        $(".submenu a").removeClass("active");
        $(this).addClass("active");

        let $menuTitle = $(this).closest(".submenu").prev(".menu-title");
        $(".menu-title").removeClass("active");
        $menuTitle.addClass("active");
        $(".menu-title .icon-right i").removeClass("fa-angle-up").addClass("fa-angle-down");
        $menuTitle.find(".icon-right i").removeClass("fa-angle-down").addClass("fa-angle-up");

        localStorage.setItem("activeMenu", $menuTitle.text().trim());
    });

    $(".menu-title, .submenu a").click(function () {
        $(".menu-title").first().removeClass("active");
    });
});





</script>
</body>
</html>