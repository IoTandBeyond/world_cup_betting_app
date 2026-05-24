<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set new password — World Cup Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="bg-light player-app">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="mb-2 text-center">
                        <i class="fa fa-key text-warning"></i> New password
                    </h3>
                    <p class="text-muted text-center small mb-4">
                        Hi <?= e($user['name']) ?>, you must replace your temporary password before continuing.
                    </p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('/password/change') ?>">
                        <?= \App\Services\Csrf::field() ?>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm password</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-pool w-100">Save and continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
