<?php
$adminPath = request_path();
$adminNav = [
    '/admin' => ['label' => 'Dashboard', 'icon' => 'fa-home'],
    '/admin/invitations' => ['label' => 'Invitations', 'icon' => 'fa-envelope'],
    '/admin/users' => ['label' => 'Users', 'icon' => 'fa-users'],
    '/admin/tournament' => ['label' => 'Tournament', 'icon' => 'fa-globe'],
    '/admin/matches' => ['label' => 'Matches', 'icon' => 'fa-futbol'],
    '/admin/results' => ['label' => 'Results', 'icon' => 'fa-trophy'],
];

$adminNavLink = static function (string $path, array $item) use ($adminPath): string {
    $active = $adminPath === $path
        || ($path === '/admin' && $adminPath === '/admin/');
    $class = 'nav-link' . ($active ? ' active' : '');

    return '<a class="' . $class . '" href="' . e(url($path)) . '">'
        . '<i class="fa ' . e($item['icon']) . ' me-2"></i>'
        . e($item['label']) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#094d2a">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= e($title ?? 'Admin') ?> — World Cup Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="admin-app bg-light">
<header class="admin-topbar sticky-top d-lg-none">
    <nav class="navbar navbar-dark bg-pool-nav">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h6">
                <i class="fa fa-shield-halved me-1"></i> Admin
            </span>
            <button class="navbar-toggler border-0 shadow-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#adminMenu"
                    aria-controls="adminMenu"
                    aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
</header>

<div class="offcanvas offcanvas-start admin-offcanvas d-lg-none"
     tabindex="-1"
     id="adminMenu"
     aria-labelledby="adminMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white" id="adminMenuLabel">Admin menu</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column admin-sidebar-nav">
            <?php foreach ($adminNav as $path => $item): ?>
                <div class="nav-item">
                    <?= $adminNavLink($path, $item) ?>
                </div>
            <?php endforeach; ?>
            <div class="nav-item mt-3">
                <a class="nav-link text-white-50" href="<?= url('/logout') ?>">
                    <i class="fa fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </nav>
    </div>
</div>

<div class="container-fluid admin-shell">
    <div class="row">
        <aside class="col-lg-2 d-none d-lg-block admin-sidebar p-3">
            <h4 class="text-white h5">Admin</h4>
            <hr class="border-secondary">
            <nav class="nav flex-column admin-sidebar-nav">
                <?php foreach ($adminNav as $path => $item): ?>
                    <div class="nav-item">
                        <?= $adminNavLink($path, $item) ?>
                    </div>
                <?php endforeach; ?>
                <div class="nav-item mt-3">
                    <a class="nav-link text-white-50" href="<?= url('/logout') ?>">
                        <i class="fa fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>
        <main class="col-lg-10 admin-main p-3 p-md-4">
            <?php
            $flashSuccess = \App\Services\Flash::get('success');
            $flashError = \App\Services\Flash::get('error');
            ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= e($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('#adminMenu .nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
        var menu = document.getElementById('adminMenu');
        if (menu && typeof bootstrap !== 'undefined') {
            bootstrap.Offcanvas.getInstance(menu)?.hide();
        }
    });
});
</script>
</body>
</html>
