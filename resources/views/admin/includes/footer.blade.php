{{-- <footer class="text-center mt-4">
    <p>&copy; {{ date('Y') }} Prabhu Steels. All rights reserved.</p>
</footer> --}}
  <!-- Bootstrap core JavaScript -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="{{ asset('js/vendor/popper.min.js') }}"></script>
  <script src="{{ asset('js/bootstrap.min.js') }}"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function () {
        $(".has-submenu > a").click(function (e) {
            e.preventDefault();
            var $parentLi = $(this).parent(); // Get parent <li>
            var $submenu = $parentLi.find(".submenu").first(); // Find submenu
            var $arrow = $(this).find(".arrow");

            // Close all other submenus (optional)
            $(".has-submenu").not($parentLi).removeClass("open").find(".submenu").slideUp();
            $(".has-submenu").not($parentLi).find(".arrow").text("↓");

            // Toggle current submenu
            $parentLi.toggleClass("open");
            $submenu.slideToggle();

            // Change arrow
            $arrow.text($parentLi.hasClass("open") ? "↑" : "↓");
        });
    });
</script>
</body>
</html>