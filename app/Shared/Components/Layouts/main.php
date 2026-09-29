<?php
use App\Core\Application;
use App\Core\Auth;
use App\Core\Session;

$signedIn = Auth::check();
$isAdmin = Session::get('role') === 'administrator';
$activeConns = $signedIn ? \App\Modules\Connection\Controllers\ConnectionController::getActiveConnections() : [];
$currentConnId = Session::get('active_connection_id');
$currentConnName = 'Local System DB';
foreach ($activeConns as $connection) {
    if ($connection['id'] == $currentConnId) $currentConnName = $connection['name'];
}
$username = Session::get('username', 'User');
$route = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$pages = [
    'dashboard' => ['Dashboard', 'fa-house'],
    'explorer' => ['Explorer', 'fa-database'],
    'structure' => ['Create Table', 'fa-table'],
    'sql' => ['SQL Editor', 'fa-code'],
    'builder' => ['Query Builder', 'fa-diagram-project'],
    'search' => ['Global Search', 'fa-magnifying-glass'],
    'connections' => ['Connections', 'fa-link'],
    'monitor' => ['Server Monitor', 'fa-desktop'],
    'users' => ['Users', 'fa-users'],
    'activity' => ['Activity Log', 'fa-clock-rotate-left']
];
$pageTitle = isset($pages[$route]) ? $pages[$route][0] : 'Sign in';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title><?= htmlspecialchars($pageTitle) ?> · DB Manager</title>
    <script>
        window.BASE_URL = <?= json_encode(rtrim(Application::asset(''), '/'), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        try { document.documentElement.dataset.bsTheme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= Application::asset('assets/css/style.css?v=' . filemtime(Application::$ROOT_DIR . '/public/assets/css/style.css')) ?>" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= Application::asset('assets/js/app.js?v=' . filemtime(Application::$ROOT_DIR . '/public/assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $signedIn ? 'workspace' : 'auth-page' ?>" data-page="<?= htmlspecialchars($route) ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-container">
    <?php if ($signedIn): ?>
        <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
        <aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
            <a class="sidebar-header" href="<?= Application::asset('dashboard') ?>">
                <i class="fas fa-database brand-icon" aria-hidden="true"></i>
                <span><strong>DB Manager</strong><small>DATABASE WORKSPACE</small></span>
            </a>
            <button type="button" class="icon-button sidebar-close" id="sidebar-close" aria-label="Close navigation"><i class="fas fa-xmark" aria-hidden="true"></i></button>
            <nav class="sidebar-nav" aria-label="Workspace">
                <?php foreach ($pages as $path => $page): ?>
                    <?php if (in_array($path, ['connections', 'monitor', 'users']) && !$isAdmin) continue; ?>
                    <?php if ($path === 'explorer'): ?><div class="nav-section-label">Database Tools</div><?php endif; ?>
                    <?php if ($path === 'connections' && $isAdmin): ?><div class="nav-section-label nav-section-divider">Administration</div><?php endif; ?>
                    <a class="nav-link <?= $route === $path ? 'active' : '' ?>" href="<?= Application::asset($path) ?>" <?= $route === $path ? 'aria-current="page"' : '' ?>>
                        <i class="fas <?= $page[1] ?>" aria-hidden="true"></i><span><?= $page[0] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="supported-engines"><i class="fas fa-database" aria-hidden="true"></i>MySQL / SQL Server</div>
                <a class="nav-link" href="<?= Application::asset('logout') ?>"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>Sign out</a>
            </div>
        </aside>
        <div class="main-content">
            <header class="top-header">
                <button class="icon-button menu-toggle" id="menu-toggle" type="button" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false"><i class="fas fa-bars" aria-hidden="true"></i></button>
                <a class="mobile-brand" href="<?= Application::asset('dashboard') ?>"><i class="fas fa-database" aria-hidden="true"></i>DB Manager</a>
                <div class="header-breadcrumb"><span>Workspace</span><span aria-hidden="true">/</span><strong><?= htmlspecialchars($pageTitle) ?></strong></div>
                <div class="dropdown connection-switcher">
                    <button class="connection-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Active connection: <?= htmlspecialchars($currentConnName) ?>">
                        <i class="fas fa-database" aria-hidden="true"></i><span><?= htmlspecialchars($currentConnName) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Switch connection</h6></li>
                        <li><a class="dropdown-item <?= !$currentConnId ? 'active' : '' ?>" href="<?= Application::asset('connection/switch') ?>">Local System DB</a></li>
                        <?php foreach ($activeConns as $connection): ?>
                        <li><a class="dropdown-item <?= $connection['id'] == $currentConnId ? 'active' : '' ?>" href="<?= Application::asset('connection/switch?id=' . (int)$connection['id']) ?>"><?= htmlspecialchars($connection['name']) ?></a></li>
                        <?php endforeach; ?>
                        <?php if ($isAdmin): ?><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="<?= Application::asset('connections') ?>"><i class="fas fa-sliders me-2" aria-hidden="true"></i>Manage connections</a></li><?php endif; ?>
                    </ul>
                </div>
                <button class="icon-button" id="themeToggle" type="button" aria-label="Switch to dark theme" title="Switch theme"><i class="fas fa-moon" aria-hidden="true"></i></button>
                <div class="dropdown user-menu">
                    <button class="avatar-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account: <?= htmlspecialchars($username) ?>"><?= htmlspecialchars(strtoupper(substr($username, 0, 1))) ?></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars($username) ?><small class="d-block mt-1"><?= htmlspecialchars(ucfirst(Session::get('role', ''))) ?></small></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= Application::asset('logout') ?>"><i class="fas fa-arrow-right-from-bracket me-2" aria-hidden="true"></i>Sign out</a></li>
                    </ul>
                </div>
            </header>
            <main class="content-wrapper" id="main-content" tabindex="-1">{{content}}</main>
            <footer class="workspace-footer"><span>DB Manager</span><span>MySQL · MariaDB · SQL Server</span></footer>
        </div>
    <?php else: ?>
        <button class="icon-button auth-theme-toggle" id="themeToggle" type="button" aria-label="Switch to dark theme"><i class="fas fa-moon" aria-hidden="true"></i></button>
        <main class="auth-main" id="main-content" tabindex="-1">{{content}}</main>
    <?php endif; ?>
    </div>
</body>
</html>
