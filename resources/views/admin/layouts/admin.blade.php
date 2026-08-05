<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - JatraPoth</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            /* Design Tokens */
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --surface: #ffffff;
            --surface-raised: #f8fafc;
            --on-surface: #1e293b;
            --on-surface-muted: #64748b;
            --border: #e2e8f0;
            --error: #dc2626;
            --success: #16a34a;
            --warning: #ea580c;

            /* Spacing (base-8) */
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;
            --space-2xl: 48px;

            /* Border radius */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 16px;

            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(0,0,0,.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,.15);

            /* Touch targets */
            --touch-target-min: 48px;

            /* Sidebar */
            --sidebar-width: 260px;
            --topbar-height: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--surface-raised);
            color: var(--on-surface);
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border);
        }

        .sidebar-brand h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .sidebar-nav {
            padding: var(--space-md) 0;
        }

        .nav-item {
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px var(--space-lg);
            color: var(--on-surface-muted);
            text-decoration: none;
            transition: all 0.2s;
            min-height: var(--touch-target-min);
            gap: 12px;
        }

        .nav-link:hover {
            background: var(--surface-raised);
            color: var(--primary);
        }

        .nav-link.active {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }

        .nav-link:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: -3px;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Topbar */
        .admin-topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--topbar-height);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 var(--space-xl);
        }

        .topbar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--on-surface);
        }

        .admin-actions {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--error);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            min-height: var(--touch-target-min);
            cursor: pointer;
        }

        .btn-logout:hover {
            background: #b91c1c;
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-logout:focus-visible {
            outline: 3px solid var(--error);
            outline-offset: 2px;
        }

        /* Main Content */
        .admin-main {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: var(--space-xl);
            min-height: calc(100vh - var(--topbar-height));
        }

        .glass-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--space-lg);
        }

        .card-header {
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-md);
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--on-surface);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }

            .admin-topbar {
                left: 0;
            }

            .admin-main {
                margin-left: 0;
                padding: var(--space-md);
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-bus"></i> JatraPoth Admin</h2>
        </div>

        <nav class="sidebar-nav">
            <ul class="list-unstyled">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('showdata') }}" class="nav-link {{ request()->routeIs('showdata') ? 'active' : '' }}">
                        <i class="fas fa-bus"></i>
                        <span>Manage Buses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.generate.view') }}" class="nav-link {{ request()->routeIs('admin.generate.view') ? 'active' : '' }}">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Generate Schedules</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin_seat_info_button') }}" class="nav-link {{ request()->routeIs('admin_seat_info_button') ? 'active' : '' }}">
                        <i class="fas fa-chair"></i>
                        <span>Seat Info</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin_show_all_user') }}" class="nav-link {{ request()->routeIs('admin_show_all_user') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('adminOrders') }}" class="nav-link {{ request()->routeIs('adminOrders') ? 'active' : '' }}">
                        <i class="fas fa-receipt"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.refund.requests') }}" class="nav-link {{ request()->routeIs('admin.refund.requests') ? 'active' : '' }}">
                        <i class="fas fa-undo"></i>
                        <span>Refund Requests</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Topbar -->
    <header class="admin-topbar">
        <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>

        <div class="admin-actions">
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-primary" target="_blank" title="View Site">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <a href="{{ route('adminLogOut') }}" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" aria-live="polite">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="assertive">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
