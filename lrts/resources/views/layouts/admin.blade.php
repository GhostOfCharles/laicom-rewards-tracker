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
</head>
<body class="laicom-page laicom-admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 p-3 sidebar shadow-sm">
                <div class="laicom-sidebar-head text-center p-3 mb-4">
                    <img src="{{ asset('images/laicom-logo.png') }}" alt="Laicom" class="mb-2">
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
                        <a class="nav-link text-dark {{ request()->routeIs('admin.receipts*') ? 'active' : '' }}" href="{{ route('admin.receipts') }}"><i class="bi bi-receipt me-2"></i>RECEIPTS <span class="badge text-bg-danger ms-1" data-admin-count="receipts" @if(($adminNavCounts['receipts'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['receipts'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.claims*') ? 'active' : '' }}" href="{{ route('admin.claims') }}"><i class="bi bi-gift me-2"></i>CLAIMS <span class="badge text-bg-danger ms-1" data-admin-count="claims" @if(($adminNavCounts['claims'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['claims'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets') }}"><i class="bi bi-life-preserver me-2"></i>TICKETS <span class="badge text-bg-danger ms-1" data-admin-count="tickets" @if(($adminNavCounts['tickets'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['tickets'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}" href="{{ route('admin.activity-log') }}"><i class="bi bi-clock-history me-2"></i>ACTIVITY LOG</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4 admin-main">
                <div class="d-flex align-items-center justify-content-between border-bottom border-2 border-dark pb-3 mb-4">
                    <div><div class="small text-muted fw-bold">LAICOM REWARDS TRACKER SYSTEM</div><h4 class="mb-0 fw-bold">ADMIN WORKSPACE</h4></div>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn laicom-btn-primary btn-sm">LOGOUT</button></form>
                </div>
                <div class="admin-scroll-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @include('components.feedback-toast')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const refreshAdminCounts = async () => {
                if (document.hidden) return;
                try {
                    const response = await fetch(@json(route('admin.nav-counts')), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!response.ok) return;
                    const counts = await response.json();
                    document.querySelectorAll('[data-admin-count]').forEach((badge) => {
                        const count = Number(counts[badge.dataset.adminCount] || 0);
                        badge.textContent = badge.dataset.adminCount === 'premium-expiry' ? `${count} EXPIRY ALERTS` : String(count);
                        badge.hidden = count < 1;
                    });
                } catch (_) {
                    // Keep the last known counts when a temporary network issue occurs.
                }
            };
            refreshAdminCounts();
            window.setInterval(refreshAdminCounts, 20000);

            const feedbackToast = document.querySelector('[data-feedback-toast]');
            if (feedbackToast) new bootstrap.Toast(feedbackToast, { delay: 6000 }).show();
        })();

        // Bootstrap modals must escape the internally scrolling content region.
        document.querySelectorAll('.laicom-admin-dashboard .modal').forEach((modal) => {
            document.body.appendChild(modal);
        });
    </script>
    @stack('scripts')
</body>
</html>
