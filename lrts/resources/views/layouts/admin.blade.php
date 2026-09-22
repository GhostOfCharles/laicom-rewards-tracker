<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LRTS Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Bootstrap Icons for the pencil, minus, and plus symbols -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; border-right: 2px solid #dee2e6; }
        .nav-link.active { font-weight: bold; background-color: #e9ecef; border-radius: 0.25rem; }
    </style>
</head>
<body class="bg-white">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 p-3 sidebar">
                <h5 class="mb-4 text-center border-bottom pb-2">ADMIN DASHBOARD</h5>
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link text-dark active border" href="{{ route('admin.dashboard') }}">PREMIUM PRODUCTS & PROMOTIONS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark border" href="#">INVENTORY</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark border" href="#">REPORTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark border" href="#">RECEIPTS</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>