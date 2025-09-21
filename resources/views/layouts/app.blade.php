<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    @stack('styles')
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- TomSelect for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <style>
        :root {
            /* Primary theme colors - Updated with a modern, more appealing palette */
            --bs-primary: #6366F1;        /* Indigo 500 */
            --bs-secondary: #4F46E5;      /* Indigo 600 */
            --bs-success: #10B981;        /* Emerald 500 */
            --bs-info: #3B82F6;           /* Blue 500 */
            --bs-warning: #F59E0B;        /* Amber 500 */
            --bs-danger: #EF4444;         /* Red 500 */
            --bs-light: #F9FAFB;          /* Gray 50 */
            --bs-dark: #1F2937;           /* Gray 800 */
            
            /* UI specific variables - Updated for better contrast and visual appeal */
            --light-bg: #F9FAFB;
            --text-color: #1F2937;
            --text-muted: #6B7280;
            --card-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1), 0 2px 4px -1px rgba(99, 102, 241, 0.06);
            --sidebar-width: 260px;
            --header-height: 60px;
            --sidebar-bg: #FFFFFF;
            --sidebar-hover: rgba(99, 102, 241, 0.1);
            --sidebar-active: rgba(99, 102, 241, 0.15);
            
            /* Additional theme colors */
            --accent: #A5B4FC;            /* Indigo 300 */
            --card-bg: #FFFFFF;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 999;
            color: var(--text-color);
            overflow-y: auto;
        }
        
        .content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: all 0.3s ease;
            min-height: 100vh;
            background-color: var(--light-bg);
        }
        
        .brand {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--bs-primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .brand-text {
            font-weight: 600;
            margin-left: 0.75rem;
            font-size: 1.25rem;
            color: var(--text-color);
        }
        
        .nav-item {
            padding: 0;
            margin: 2px 0;
        }
        
        .nav-section-title {
            color: var(--text-muted);
            padding: 1.25rem 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            color: var(--text-color);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 0 0.75rem;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: var(--bs-primary);
            transform: translateX(2px);
        }
        
        .nav-link.active {
            background-color: var(--sidebar-active);
            color: var(--bs-primary);
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .nav-icon {
            font-size: 1rem;
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
            opacity: 0.9;
        }
        
        .nav-chevron {
            margin-left: auto;
            opacity: 0.8;
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }
        
        [aria-expanded="true"] .nav-chevron {
            transform: rotate(90deg);
            opacity: 1;
        }
        
        .sidebar .collapse {
            background-color: transparent;
            padding-left: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar .collapse.show {
            max-height: 1000px;
        }
        
        .sidebar .collapse .nav-link {
            padding-left: 3.5rem;
            font-size: 0.9rem;
            color: var(--text-color);
            background-color: transparent;
        }
        
        .sidebar .collapse .nav-link:hover {
            color: var(--bs-primary);
            background-color: var(--sidebar-hover);
        }
        
        .sidebar .collapse .nav-link.active {
            color: var(--bs-primary);
            background-color: var(--sidebar-active);
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                box-shadow: none;
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar.show {
                margin-left: 0;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            }
            
            .content.shifted {
                margin-left: var(--sidebar-width);
            }
        }
        
        .navbar {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        
        .navbar-toggler:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .card {
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: white;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .card-header {
            border-radius: 1rem 1rem 0 0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: white;
            padding: 1.25rem 1.5rem;
        }
        
        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #9F83DF 0%, #9ECFFF 100%);
            border-color: #9F83DF;
            box-shadow: 0 2px 4px rgba(159, 131, 223, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #8b73c9 0%, #87b9ea 100%);
            border-color: #8b73c9;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(159, 131, 223, 0.4);
        }
        
        .btn-light {
            background-color: var(--bs-light);
            border-color: var(--bs-light);
            color: var(--text-color);
        }
        
        .btn-light:hover {
            background-color: #e9ecef;
            border-color: #e9ecef;
        }
        
        .text-primary {
            color: var(--bs-primary) !important;
        }
        
        .text-secondary {
            color: var(--bs-secondary) !important;
        }
        
        .bg-primary-subtle {
            background-color: rgba(159, 131, 223, 0.15) !important;
        }
        
        .bg-success-subtle {
            background-color: rgba(199, 245, 217, 0.15) !important;
        }
        
        .bg-warning-subtle {
            background-color: rgba(255, 229, 180, 0.15) !important;
        }
        
        .fs-xs {
            font-size: 0.75rem !important;
        }
        
        .fs-sm {
            font-size: 0.875rem !important;
        }

        .dropdown-menu {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0.5rem;
            min-width: 200px;
        }
        
        .dropdown-item {
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: rgba(159, 131, 223, 0.1);
            transform: translateX(2px);
        }
        
        .dropdown-item i {
            width: 1.25rem;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .avatar {
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #9F83DF 0%, #9ECFFF 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Button styles */
        .btn-icon {
            width: 38px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Animation styles */
        .sidebar .collapse {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar .collapse.show {
            max-height: 1000px;
        }
        
        /* Table styles */
        .table-responsive {
            max-height: 500px !important; /* Fixed height */
            overflow-y: auto !important;
            overflow-x: auto !important;
            display: block !important;
            width: 100% !important;
            border: 1px solid #dee2e6;
        }
        
        .table-responsive thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-responsive thead th {
            background-color: #f8f9fa !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        
        /* Ensure tables don't extend endlessly */
        .wide-table {
            margin-bottom: 0 !important;
        }
        
        /* Disable any styles that might interfere with scrolling */
        .disable-fixed-scrollbar {
            max-height: 500px !important;
            overflow-y: auto !important;
            overflow-x: auto !important;
        }
        
        /* Fix horizontal scrolling */
        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            display: block !important;
            width: 100% !important;
        }
        
        /* Make tables have proper width and horizontal scroll */
        .table-responsive table {
            width: auto !important;
            min-width: 100% !important;
        }
        
        /* Page transitions */
        .page-enter {
            opacity: 0;
            transform: translateY(10px);
        }
        
        .page-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        /* Custom styles for specific components */
        .stat-card {
            border-radius: 1rem;
            border-left: 4px solid var(--bs-primary);
            box-shadow: var(--card-shadow);
            background-color: var(--card-bg);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(159, 131, 223, 0.15);
        }
        
        .stat-card.primary {
            border-left-color: var(--bs-primary);
        }
        
        .stat-card.secondary {
            border-left-color: var(--bs-secondary);
        }
        
        .stat-card.info {
            border-left-color: var(--bs-info);
        }
        
        .stat-card.warning {
            border-left-color: var(--bs-warning);
        }
        
        .stat-card.danger {
            border-left-color: var(--bs-danger);
        }
        
        /* Improved table header styling */
        .table-responsive.disable-fixed-scrollbar thead th {
            background-color: white;
            position: sticky;
            top: 0;
            z-index: 11;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        /* Table with fixed headers that work with horizontal scroll */
        .table-responsive table thead th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        @include('components.sidebar')
    </div>
    
    <div class="content" id="content">
        @include('components.navbar')
        
        <div class="py-3">
            @yield('content')
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="{{ asset('js/fixed-table-scroll.js') }}"></script> -->
    <script>
        // Disable fixed-table-scroll.js
        window.disableFixedTableScroll = true;
        
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM loaded, initializing tables...");
            
            // Disable fixed-table-scroll.js completely
            if (window.disableFixedTableScroll) {
                console.log("Disabling fixed-table-scroll.js");
                window.disableFixedTableScroll = true;
            }
            
            // Fix untuk tabel scrolling horizontal
            const tableResponsives = document.querySelectorAll('.table-responsive');
            console.log("Found " + tableResponsives.length + " responsive tables");
            
            tableResponsives.forEach((container, index) => {
                console.log("Processing table #" + index);
                
                // Remove any dynamic inline styles that might be causing issues
                container.style = "";
                
                // Apply only the essential styles directly
                container.style.maxHeight = "500px";
                container.style.overflowY = "auto";
                container.style.overflowX = "auto";
                container.style.display = "block";
                container.style.width = "100%";
                
                const table = container.querySelector('table');
                if (table) {
                    console.log("Table found in container #" + index);
                    // Reset any dynamic styles
                    table.style = "";
                    
                    // Set minimal styling
                    table.style.width = "100%";
                    if (table.classList.contains('wide-table')) {
                        table.style.minWidth = "1200px";
                        console.log("Applied wide-table style");
                    }
                    
                    // Disable all event listeners for scroll that might be interfering
                    const newContainer = container.cloneNode(true);
                    container.parentNode.replaceChild(newContainer, container);
                }
            });
            
            const sidebarToggleBtn = document.getElementById('sidebarToggle');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('show');
                    document.getElementById('content').classList.toggle('shifted');
                });
            }
            
            // Responsive behavior
            function checkWindowSize() {
                const sidebar = document.getElementById('sidebar');
                const content = document.getElementById('content');
                
                if (window.innerWidth <= 991.98) {
                    sidebar.classList.remove('show');
                    content.classList.remove('shifted');
                } else {
                    sidebar.classList.remove('show');
                    content.classList.remove('shifted');
                }
            }
            
            window.addEventListener('resize', checkWindowSize);
            checkWindowSize();
            
            // Dropdown arrow rotation
            const dropdownToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const icon = this.querySelector('.nav-chevron');
                    if (icon) {
                        if (this.getAttribute('aria-expanded') === 'true') {
                            icon.style.transform = 'rotate(90deg)';
                        } else {
                            icon.style.transform = 'rotate(0deg)';
                        }
                    }
                    
                    // Animation for collapsing element
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        if (this.getAttribute('aria-expanded') === 'true') {
                            targetElement.style.maxHeight = '0';
                            setTimeout(() => {
                                targetElement.style.maxHeight = targetElement.scrollHeight + 'px';
                            }, 10);
                        } else {
                            targetElement.style.maxHeight = targetElement.scrollHeight + 'px';
                            setTimeout(() => {
                                targetElement.style.maxHeight = '0';
                            }, 10);
                        }
                    }
                });
                
                // Initial state
                const collapse = document.querySelector(toggle.getAttribute('href'));
                const icon = toggle.querySelector('.nav-chevron');
                
                if (icon && collapse && collapse.classList.contains('show')) {
                    icon.style.transform = 'rotate(90deg)';
                    collapse.style.maxHeight = collapse.scrollHeight + 'px';
                } else if (collapse) {
                    collapse.style.maxHeight = '0';
                }
            });
            
            // Highlight active menu item
            const currentPath = window.location.pathname;
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                if (link.getAttribute('href') !== '#' && link.getAttribute('href') !== null) {
                    const linkPath = new URL(link.href, window.location.origin).pathname;
                    if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                        link.classList.add('active');
                        
                        // If it's a submenu item, expand its parent
                        const parentCollapse = link.closest('.collapse');
                        if (parentCollapse) {
                            const parentToggle = document.querySelector(`[href="#${parentCollapse.id}"]`);
                            if (parentToggle) {
                                parentToggle.setAttribute('aria-expanded', 'true');
                                parentCollapse.classList.add('show');
                                parentCollapse.style.maxHeight = parentCollapse.scrollHeight + 'px';
                                
                                const icon = parentToggle.querySelector('.nav-chevron');
                                if (icon) {
                                    icon.style.transform = 'rotate(90deg)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    
    <!-- Diagnostic script for debugging -->
    <script>
        // Log any JavaScript errors
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.message, 'at', e.filename, 'line', e.lineno);
            
            // Create visible error message for white screen debugging
            const errorDiv = document.createElement('div');
            errorDiv.style.position = 'fixed';
            errorDiv.style.top = '0';
            errorDiv.style.left = '0';
            errorDiv.style.right = '0';
            errorDiv.style.padding = '10px';
            errorDiv.style.background = 'red';
            errorDiv.style.color = 'white';
            errorDiv.style.zIndex = '9999';
            errorDiv.innerHTML = 'JavaScript Error: ' + e.message + ' at ' + e.filename + ' line ' + e.lineno;
            document.body.appendChild(errorDiv);
        });
        
        // Log when Chart.js is loaded successfully
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (window.Chart) {
                    console.log('Chart.js loaded successfully');
                } else {
                    console.error('Chart.js not loaded');
                    
                    // Create visible error message
                    const errorDiv = document.createElement('div');
                    errorDiv.style.position = 'fixed';
                    errorDiv.style.top = '0';
                    errorDiv.style.left = '0';
                    errorDiv.style.right = '0';
                    errorDiv.style.padding = '10px';
                    errorDiv.style.background = 'orange';
                    errorDiv.style.color = 'white';
                    errorDiv.style.zIndex = '9999';
                    errorDiv.innerHTML = 'Chart.js not loaded. This may cause white screens in analytics pages.';
                    document.body.appendChild(errorDiv);
                }
            }, 1000);
        });
    </script>
    
    @stack('scripts')
</body>
</html>