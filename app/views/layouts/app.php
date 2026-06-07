<?php
use App\Services\Auth;
use App\Services\TournamentContext;

$currentPath = request_path();
$navItems = [
    '/dashboard' => ['label' => 'My Bets', 'icon' => 'fa-futbol'],
    '/bonus' => ['label' => 'Bonus', 'icon' => 'fa-star'],
    '/leaderboard' => ['label' => 'Leaderboard', 'icon' => 'fa-ranking-star'],
];

$navLink = static function (string $path, array $item) use ($currentPath): string {
    $active = $currentPath === $path
        || ($path === '/dashboard' && $currentPath === '/');
    $class = 'nav-link' . ($active ? ' active' : '');

    return '<a class="' . $class . '" href="' . e(url($path)) . '">'
        . '<i class="fa ' . e($item['icon']) . ' me-2"></i>'
        . e($item['label']) . '</a>';
};
$user = Auth::user();
$currentTournament = $user ? TournamentContext::currentTournament($user) : null;
$userTournaments = $user ? TournamentContext::availableTournaments($user) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#094d2a">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= e($title ?? 'World Cup Pool') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="player-app">
<header class="app-header sticky-top">
    <nav class="navbar navbar-expand-lg navbar-dark bg-pool-nav">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= url('/dashboard') ?>">
                <i class="fa fa-trophy text-warning me-1"></i>
                <span class="d-none d-sm-inline">World Cup Pool</span>
                <span class="d-inline d-sm-none">Pool</span>
            </a>

            <button class="navbar-toggler d-lg-none border-0 shadow-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#appMenu"
                    aria-controls="appMenu"
                    aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-lg-end d-none d-lg-flex">
                <ul class="navbar-nav align-items-lg-center">
                    <?php foreach ($navItems as $path => $item): ?>
                        <li class="nav-item">
                            <?= $navLink($path, $item) ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!empty($user)): ?>
                        <li class="nav-item">
                            <span class="nav-link py-lg-2 text-nowrap">
                                <i class="fa fa-user-circle me-1"></i><?= e($user['name']) ?>
                            </span>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-nowrap" href="<?= url('/logout') ?>">
                            <i class="fa fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="offcanvas offcanvas-start app-offcanvas d-lg-none"
     tabindex="-1"
     id="appMenu"
     aria-labelledby="appMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white" id="appMenuLabel">Menu</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column app-mobile-nav">
            <?php foreach ($navItems as $path => $item): ?>
                <div class="nav-item">
                    <?= $navLink($path, $item) ?>
                </div>
            <?php endforeach; ?>
        </nav>
        <hr class="border-secondary opacity-50">
        <?php if (!empty($user)): ?>
            <p class="text-white-50 small mb-2 px-2">
                <i class="fa fa-user-circle me-1"></i><?= e($user['name']) ?>
            </p>
        <?php endif; ?>
        <a class="nav-link" href="<?= url('/logout') ?>">
            <i class="fa fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</div>

<main class="container app-main py-3 py-md-4">
    <?php if ($user && count($userTournaments) > 1 && $currentTournament): ?>
        <form method="POST"
              action="<?= url('/tournament/switch') ?>"
              class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <?= \App\Services\Csrf::field() ?>
            <input type="hidden" name="redirect" value="<?= e($currentPath) ?>">
            <label class="small text-muted mb-0">Tournament:</label>
            <select name="tournament_id"
                    class="form-select form-select-sm w-auto"
                    onchange="this.form.submit()">
                <?php foreach ($userTournaments as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === (int) $currentTournament['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?> (<?= (int) $t['year'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
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
<script>
document.querySelectorAll('#appMenu .nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
        var menu = document.getElementById('appMenu');
        if (menu && typeof bootstrap !== 'undefined') {
            bootstrap.Offcanvas.getInstance(menu)?.hide();
        }
    });
});
</script>
</body>
</html>
