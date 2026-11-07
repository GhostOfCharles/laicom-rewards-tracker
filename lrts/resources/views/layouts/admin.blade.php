<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTS Admin Portal</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" type="image/x-icon">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Include Bootstrap Icons for the pencil, minus, and plus symbols -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rts.css') }}?v={{ filemtime(public_path('css/rts.css')) }}">
</head>
<body class="rts-page rts-admin-dashboard" data-pulse-url="{{ route('admin.nav-counts') }}" data-pulse-role="admin">
    <div class="rts-admin-menu-bar"><button class="btn btn-sm btn-outline-light" type="button" id="adminMenuToggle" aria-controls="adminSidebar" aria-expanded="false"><i class="bi bi-list"></i><span>Menu</span></button><strong>RTS Admin</strong></div>
    <div id="adminSidebarBackdrop" class="rts-admin-sidebar-backdrop" hidden></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 p-3 sidebar shadow-sm" id="adminSidebar"><button type="button" class="btn btn-sm btn-outline-secondary d-md-none mb-3" data-admin-menu-close aria-label="Close navigation">Close menu</button>
                <div class="rts-sidebar-head text-center p-3 mb-4">
                    <img src="{{ asset('images/rts-logo.png') }}" alt="Rewards Tracker System" class="mb-2">
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
                        <a class="nav-link text-dark {{ request()->routeIs('admin.receipts*') ? 'active' : '' }}" href="{{ route('admin.receipts') }}"><i class="bi bi-receipt me-2"></i>RECEIPTS <span class="badge text-bg-danger ms-1" aria-live="polite" data-admin-count="receipts" @if(($adminNavCounts['receipts'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['receipts'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.claims*') ? 'active' : '' }}" href="{{ route('admin.claims') }}"><i class="bi bi-gift me-2"></i>CLAIMS <span class="badge text-bg-danger ms-1" aria-live="polite" data-admin-count="claims" @if(($adminNavCounts['claims'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['claims'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets') }}"><i class="bi bi-life-preserver me-2"></i>TICKETS <span class="badge text-bg-danger ms-1" aria-live="polite" data-admin-count="tickets" @if(($adminNavCounts['tickets'] ?? 0) < 1) hidden @endif>{{ $adminNavCounts['tickets'] ?? 0 }}</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}" href="{{ route('admin.activity-log') }}"><i class="bi bi-clock-history me-2"></i>ACTIVITY LOG</a>
                    </li>
                    <li class="nav-item"><a class="nav-link text-dark {{ request()->routeIs('admin.customers*') ? 'active' : '' }}" href="{{ route('admin.customers') }}"><i class="bi bi-people me-2"></i>CUSTOMER ACCOUNTS</a></li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4 admin-main">
                <div class="d-flex align-items-center justify-content-between border-bottom border-2 border-dark pb-3 mb-4">
                    <div><div class="small text-muted fw-bold">REWARDS TRACKER SYSTEM</div><h4 class="mb-0 fw-bold">ADMIN WORKSPACE</h4></div>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn rts-btn-primary btn-sm">LOGOUT</button></form>
                </div>
                <div class="admin-scroll-content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @include('components.feedback-toast')
    <script src="{{ asset('js/admin-menu.js') }}?v={{ filemtime(public_path('js/admin-menu.js')) }}" defer></script>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}?v={{ filemtime(public_path('vendor/bootstrap/bootstrap.bundle.min.js')) }}"></script>
    <script>
        (() => {
            const feedbackToast = document.querySelector('[data-feedback-toast]');
            if (feedbackToast) new bootstrap.Toast(feedbackToast, { delay: 6000 }).show();
        })();

        // Bootstrap modals must escape the internally scrolling content region.
        document.querySelectorAll('.rts-admin-dashboard .modal').forEach((modal) => {
            document.body.appendChild(modal);
        });
    </script>
    <script src="{{ asset('js/rts-poll.js') }}?v={{ filemtime(public_path('js/rts-poll.js')) }}"></script>
    <script src="{{ asset('js/form-feedback.js') }}?v={{ filemtime(public_path('js/form-feedback.js')) }}"></script>
    @stack('scripts')
</body>
</html>
