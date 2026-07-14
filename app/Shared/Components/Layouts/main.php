<?php
$activeConns = [];
$currentConnId = null;
$currentConnName = 'Local System DB';

if (\App\Core\Auth::check()) {
    $activeConns = \App\Modules\Connection\Controllers\ConnectionController::getActiveConnections();
    $currentConnId = \App\Core\Session::get('active_connection_id');
    foreach ($activeConns as $c) {
        if ($c['id'] == $currentConnId) {
            $currentConnName = $c['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getenv('APP_NAME') ?: 'Web Database Manager' ?></title>
    
    <script>
        window.BASE_URL = "<?= rtrim(\App\Core\Application::asset(''), '/') ?>";
    </script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= \App\Core\Application::asset('assets/css/style.css') ?>" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= \App\Core\Application::asset('assets/js/app.js') ?>"></script>
    
    <style>
        /* Theme overrides for DataTables and basic elements */
        [data-bs-theme="light"] {
            --bs-body-bg: #f8f9fa;
        }
        [data-bs-theme="dark"] {
            --bs-body-bg: #0f172a;
        }
        
        .sidebar {
            transition: background-color 0.3s;
        }
        
        .top-header {
            transition: background-color 0.3s;
        }
    </style>
</head>
<body class="bg-body text-body">
    <div class="app-container">
        <?php if (\App\Core\Auth::check()): ?>
        <!-- Sidebar -->
        <aside class="sidebar bg-body-tertiary border-end">
            <div class="sidebar-header border-bottom">
                <i class="fas fa-database text-primary"></i>
                <span class="logo-text ms-2">DB Manager</span>
            </div>
            <ul class="nav flex-column sidebar-nav overflow-auto flex-fill py-2">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('dashboard') ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <h6 class="sidebar-heading px-3 mt-4 mb-2 text-muted text-uppercase" style="font-size: 0.75rem;">Database Tools</h6>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/explorer') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('explorer') ?>">
                        <i class="fas fa-sitemap"></i> Explorer
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/structure') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('structure') ?>">
                        <i class="fas fa-table"></i> Create Table
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= stripos($_SERVER['REQUEST_URI'], '/sql') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('sql') ?>">
                        <i class="fas fa-terminal"></i> SQL Editor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/builder') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('builder') ?>">
                        <i class="fas fa-magic"></i> Query Builder
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/search') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('search') ?>">
                        <i class="fas fa-search"></i> Global Search
                    </a>
                </li>
                
                <?php if (\App\Core\Session::get('role') === 'administrator'): ?>
                <h6 class="sidebar-heading px-3 mt-4 mb-2 text-muted text-uppercase" style="font-size: 0.75rem;">Administration</h6>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/connections') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('connections') ?>">
                        <i class="fas fa-network-wired"></i> Connections
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/monitor') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('monitor') ?>">
                        <i class="fas fa-heartbeat"></i> Server Monitor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('users') ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/activity') !== false ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('activity') ?>">
                        <i class="fas fa-history"></i> Activity Log
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-danger" href="<?= \App\Core\Application::asset('logout') ?>">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content Wrapper -->
        <main class="main-content">
            <header class="top-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center px-4">
                <div class="d-flex align-items-center">
                    <!-- Connection Switcher -->
                    <div class="dropdown me-4">
                        <button class="btn btn-outline-secondary dropdown-toggle border-0 fw-bold d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-server text-info me-2"></i>
                            <?= htmlspecialchars($currentConnName) ?>
                        </button>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item <?= !$currentConnId ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('connection/switch') ?>">Local System DB</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($activeConns as $c): ?>
                                <li><a class="dropdown-item <?= ($c['id'] == $currentConnId) ? 'active' : '' ?>" href="<?= \App\Core\Application::asset('connection/switch?id=' . $c['id']) ?>"><?= htmlspecialchars($c['name']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary" href="<?= \App\Core\Application::asset('connections') ?>"><i class="fas fa-cog me-2"></i>Manage Connections</a></li>
                        </ul>
                    </div>
                </div>
                <div class="header-user d-flex align-items-center">
                    <!-- Theme Toggle -->
                    <button class="btn btn-link text-body text-decoration-none me-3 fs-5" id="themeToggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-body text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-2 text-end">
                                <div class="fw-bold"><?= htmlspecialchars(\App\Core\Session::get('username') ? \App\Core\Session::get('username') : 'User') ?></div>
                                <div class="small text-muted"><?= ucfirst(htmlspecialchars(\App\Core\Session::get('role') ? \App\Core\Session::get('role') : '')) ?></div>
                            </div>
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                                <?= strtoupper(substr(\App\Core\Session::get('username') ? \App\Core\Session::get('username') : 'U', 0, 1)) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="<?= \App\Core\Application::asset('logout') ?>"><i class="fas fa-sign-out-alt me-2"></i>Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <div class="content-wrapper p-4 bg-body">
                {{content}}
            </div>
        </main>
        <?php else: ?>
            <!-- No Sidebar layout for login/public pages -->
            <main class="w-100 h-100 d-flex align-items-center justify-content-center bg-body">
                {{content}}
            </main>
        <?php endif; ?>
    </div>
    
    <!-- Global Notification System -->
    <script>
        function notify(type, message) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Theme Switcher Logic
        $(document).ready(function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            setTheme(savedTheme);

            $('#themeToggle').click(function() {
                const currentTheme = $('html').attr('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });

            function setTheme(theme) {
                $('html').attr('data-bs-theme', theme);
                localStorage.setItem('theme', theme);
                
                // Update icon
                if (theme === 'light') {
                    $('#themeToggle i').removeClass('fa-moon').addClass('fa-sun text-warning');
                } else {
                    $('#themeToggle i').removeClass('fa-sun text-warning').addClass('fa-moon');
                }
            }
        });
    </script>
</body>
</html>
