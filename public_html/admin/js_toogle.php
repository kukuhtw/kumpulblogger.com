<script>
function toggleSidebar(forceClose) {
    var sidebar = document.getElementById('sidebar');
    var mainContent = document.getElementById('mainContent');
    var backdrop = document.getElementById('sidebarBackdrop');
    var footer = document.querySelector('.footer');
    if (!sidebar) return;
    if (window.innerWidth < 992) {
        var open = forceClose !== true && !sidebar.classList.contains('open');
        sidebar.classList.toggle('open', open);
        if (backdrop) backdrop.classList.toggle('show', open);
        document.body.style.overflow = open ? 'hidden' : '';
    } else {
        sidebar.classList.toggle('hidden');
        if (mainContent) mainContent.classList.toggle('shifted');
        if (footer) footer.classList.toggle('shifted');
    }
}
window.addEventListener('resize', function () {
    if (window.innerWidth >= 992) {
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar) sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
});
</script>
