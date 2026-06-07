<?php
$title = 'Dashboard';
ob_start();
?>
<h1 class="h3 mb-4">Admin Dashboard</h1>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="text-muted">Users</h5>
                <h2 class="mb-2"><?= (int) $stats['users'] ?></h2>
                <a href="<?= url('/admin/users') ?>" class="small">View all users →</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="text-muted">Tournaments</h5>
                <h2 class="mb-0"><?= (int) ($stats['tournaments'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="text-muted">Matches</h5>
                <h2 class="mb-0"><?= (int) $stats['matches'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="text-muted">Scheduled</h5>
                <h2 class="mb-0"><?= (int) $stats['pending_matches'] ?></h2>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
