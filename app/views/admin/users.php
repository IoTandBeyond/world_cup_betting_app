<?php
$title = !empty($tournament) && empty($isGlobalList)
    ? 'Users — ' . $tournament['name']
    : 'Users';
$isSuperAdmin = $isSuperAdmin ?? false;
$isGlobalList = $isGlobalList ?? false;
$filterTournamentId = $filterTournamentId ?? 0;
ob_start();
?>
<h1 class="h3 mb-4">Users</h1>

<?php if ($isSuperAdmin && !empty($tournaments)): ?>
    <form method="GET" action="<?= url('/admin/users') ?>" class="row g-2 align-items-end mb-4">
        <div class="col-md-6 col-lg-4">
            <label class="form-label small">Filter by tournament</label>
            <select name="tournament_id" class="form-select" onchange="this.form.submit()">
                <option value="">All users</option>
                <?php foreach ($tournaments as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"
                        <?= (int) $t['id'] === (int) $filterTournamentId ? 'selected' : '' ?>>
                        <?= e($t['name']) ?> (<?= (int) $t['year'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
<?php elseif (!empty($tournament)): ?>
    <p class="text-muted mb-4">
        Players in <strong><?= e($tournament['name']) ?></strong>
    </p>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped bg-white shadow-sm align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <?php if ($isSuperAdmin): ?>
                    <th>Role</th>
                <?php endif; ?>
                <th>Status</th>
                <th>Policy</th>
                <th>Tournament</th>
                <?php if ($isSuperAdmin || !empty($tournament)): ?>
                    <th></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <?php if ($isSuperAdmin): ?>
                        <td>
                            <span class="badge bg-<?= match ($u['role'] ?? '') {
                                'admin' => 'dark',
                                'host' => 'info',
                                default => 'secondary',
                            } ?> text-uppercase">
                                <?= e($u['role'] ?? 'user') ?>
                            </span>
                        </td>
                    <?php endif; ?>
                    <td>
                        <?php if ((int) ($u['is_active'] ?? 0)): ?>
                            <span class="text-success">Active</span>
                        <?php else: ?>
                            <span class="text-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if (!empty($u['policy_accepted_at'])): ?>
                            <?= e($u['policy_accepted_at']) ?>
                            <?php if (!empty($u['policy_version'])): ?>
                                <br><span class="text-muted">v<?= e($u['policy_version']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($u['tournament_label'] ?? '—') ?></td>
                    <td class="text-nowrap">
                        <?php if (($u['role'] ?? 'user') === 'user'): ?>
                            <form method="POST"
                                  action="<?= url('/admin/users/resend-password') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Send a new temporary password? Their current password will stop working.');">
                                <?= \App\Services\Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <?php if (!empty($tournament)): ?>
                                    <input type="hidden" name="tournament_id" value="<?= (int) $tournament['id'] ?>">
                                <?php endif; ?>
                                <button type="submit"
                                        class="btn btn-sm btn-outline-primary"
                                        <?= (int) ($u['is_active'] ?? 0) ? '' : 'disabled title="Activate user first"' ?>>
                                    <i class="fa fa-envelope me-1"></i>Resend password
                                </button>
                            </form>
                            <?php if ($isSuperAdmin): ?>
                                <form method="POST" action="<?= url('/admin/users/toggle') ?>" class="d-inline">
                                    <?= \App\Services\Csrf::field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= (int) ($u['is_active'] ?? 0) ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?= (int) ($u['is_active'] ?? 0) ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" class="text-muted text-center py-4">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
