<!-- Main Navbar -->
<nav class>
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"></i></a>
        </li>
    </ul>
</nav>

<!-- /*<style> Custom Navbar Styles
.main-header {
    border-bottom: 1px solid #dee2e6;
    background: linear-gradient(to right, #ffffff, #f8f9fa);
    box-shadow: 0 2px 4px rgba(0, 0, 0, .08);
}

.navbar-light .navbar-nav .nav-link {
    color: #495057;
    font-weight: 500;
}

.navbar-light .navbar-nav .nav-link:hover {
    color: #007bff;
}
</style>
 */ -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add active class to current page link
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>