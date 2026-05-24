<?php
$title = 'Invitations';
ob_start();
?>
<h1 class="h3 mb-4">Invitations</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Send invitation by email</h5>
        <p class="small text-muted mb-3">
            Creates the user account, sends a <strong>temporary password</strong> from
            <code>no-reply@iot4b.ca</code>, and requires a new password on first login.
        </p>
        <form method="POST" action="<?= url('/admin/invitations') ?>" class="row g-2">
            <?= \App\Services\Csrf::field() ?>
            <div class="col-md-4">
                <label class="form-label small">Name (optional)</label>
                <input type="text" name="name" class="form-control" placeholder="John Smith">
            </div>
            <div class="col-md-5">
                <label class="form-label small">Email</label>
                <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-envelope me-1"></i> Send email
                </button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped bg-white shadow-sm">
        <thead>
            <tr>
                <th>Email</th>
                <th>Invited by</th>
                <th>Expires</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invitations as $inv): ?>
                <tr>
                    <td><?= e($inv['email']) ?></td>
                    <td><?= e($inv['invited_by_name']) ?></td>
                    <td><?= e($inv['expires_at']) ?></td>
                    <td>
                        <?php if ($inv['used_at']): ?>
                            <span class="badge bg-success">Account created</span>
                        <?php elseif (strtotime($inv['expires_at']) < time()): ?>
                            <span class="badge bg-secondary">Expired</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
