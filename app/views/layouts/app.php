<?php
$currentPath = request_path();
$navItems = [
    '/dashboard' => ['label' => 'My Bets', 'icon' => 'fa-futbol'],
    '/bonus' => ['label' => 'Bonus', 'icon' => 'fa-star'],
    '/leaderboard' => ['label' => 'Leaderboard', 'icon' => 'fa-ranking-star'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'World Cup Pool') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="player-app">
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/dashboard') ?>">
            <i class="fa fa-trophy text-warning me-1"></i> World Cup Pool
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($navItems as $path => $item): ?>
                    <?php
                    $active = $currentPath === $path
                        || ($path === '/dashboard' && $currentPath === '/');
                    ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $active ? ' active' : '' ?>"
                           href="<?= url($path) ?>">
                            <i class="fa <?= e($item['icon']) ?> me-1"></i>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <?php if (!empty($user)): ?>
                    <li class="nav-item">
                        <span class="nav-link py-2">
                            <i class="fa fa-user-circle me-1"></i><?= e($user['name']) ?>
                        </span>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/logout') ?>">
                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php
    $flashSuccess = \App\Services\Flash::get('success');
    $flashError = \App\Services\Flash::get('error');
    ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= e($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
