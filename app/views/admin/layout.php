<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> — World Cup Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white">Admin</h4>
            <hr class="border-secondary">
            <ul class="nav flex-column">
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin') ?>" class="nav-link text-white"><i class="fa fa-home me-2"></i>Dashboard</a>
                </li>
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin/invitations') ?>" class="nav-link text-white"><i class="fa fa-envelope me-2"></i>Invitations</a>
                </li>
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin/users') ?>" class="nav-link text-white"><i class="fa fa-users me-2"></i>Users</a>
                </li>
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin/tournament') ?>" class="nav-link text-white"><i class="fa fa-globe me-2"></i>Tournament</a>
                </li>
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin/matches') ?>" class="nav-link text-white"><i class="fa fa-futbol me-2"></i>Matches</a>
                </li>
                <li class="nav-item mb-1">
                    <a href="<?= url('/admin/results') ?>" class="nav-link text-white"><i class="fa fa-trophy me-2"></i>Results</a>
                </li>
                <li class="nav-item mt-3">
                    <a href="<?= url('/logout') ?>" class="nav-link text-white-50"><i class="fa fa-sign-out-alt me-2"></i>Logout</a>
                </li>
            </ul>
        </nav>
        <main class="col-md-9 col-lg-10 p-4">
            <?php
            $flashSuccess = \App\Services\Flash::get('success');
            $flashError = \App\Services\Flash::get('error');
            ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
