<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — World Cup Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="bg-light player-app">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center">
                        <i class="fa fa-trophy text-warning"></i> World Cup Pool
                    </h3>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <?php $success = \App\Services\Flash::get('success'); ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= e($success) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('/login') ?>">
                        <?= \App\Services\Csrf::field() ?>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= e($prefillEmail ?? '') ?>"
                                   required
                                   autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control font-monospace"
                                   required
                                   autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-pool w-100">Log in</button>
                    </form>
                    <p class="text-muted small mt-3 mb-0 text-center">
                        Use the <strong>temporary password</strong> from your invitation email
                        (not the long link token). You will accept the rules and set a new password on first login.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
