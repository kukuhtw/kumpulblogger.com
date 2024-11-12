
<!-- JavaScript to handle sidebar toggle -->
<script>
    function toggleSidebar() {
        var sidebar = document.getElementById("sidebar");
        var mainContent = document.getElementById("mainContent");

        // Toggle the class for the sidebar
        sidebar.classList.toggle("hidden");

        // Adjust the main content margin
        mainContent.classList.toggle("shifted");
    }
</script>