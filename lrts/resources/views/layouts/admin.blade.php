<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Bootstrap Icons for the pencil, minus, and plus symbols -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/laicom.css') }}">
    <style>
        .sidebar { min-height: 100vh; background: #fff; border-right: 2px solid #12284c; }
        .sidebar .nav-link { border: 2px solid #12284c !important; border-radius: 0; font-size: .78rem; font-weight: 800; letter-spacing: .03em; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #12284c; color: #fff !important; }
    </style>
</head>
<body class="laicom-page laicom-admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 p-3 sidebar shadow-sm">
                <div class="laicom-sidebar-head text-center p-3 mb-4">
                    <img src="{{ asset('images/laicom-logo.png') }}" alt="Laicom" style="height: 35px;" class="mb-2">
                    <div class="small fw-bold">ADMIN DASHBOARD</div>
                </div>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-gift me-2"></i>PREMIUM &amp; PROMOS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}" href="{{ route('admin.inventory') }}"><i class="bi bi-box-seam me-2"></i>INVENTORY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-bar-chart me-2"></i>REPORTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.receipts*') ? 'active' : '' }}" href="{{ route('admin.receipts') }}"><i class="bi bi-receipt me-2"></i>RECEIPTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets') }}"><i class="bi bi-life-preserver me-2"></i>TICKETS</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4 admin-main">
                <div class="d-flex align-items-center justify-content-between border-bottom border-2 border-dark pb-3 mb-4">
                    <div><div class="small text-muted fw-bold">LAICOM REWARDS TRACKER SYSTEM</div><h4 class="mb-0 fw-bold" style="color: #12284c;">ADMIN WORKSPACE</h4></div>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn laicom-btn-primary btn-sm">LOGOUT</button></form>
                </div>
                <div class="admin-scroll-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap modals must escape the internally scrolling content region.
        document.querySelectorAll('.laicom-admin-dashboard .modal').forEach((modal) => {
            document.body.appendChild(modal);
        });
    </script>
</body>
</html>
