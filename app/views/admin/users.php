<?php
$title = 'Users';
ob_start();
?>
<h1 class="h3 mb-4">Users</h1>
<div class="table-responsive">
    <table class="table table-striped bg-white shadow-sm">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td>
                        <?= (int) $u['is_active'] ? 'Active' : 'Inactive' ?>
                    </td>
                    <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <form method="POST" action="<?= url('/admin/users/toggle') ?>" class="d-inline">
                                <?= \App\Services\Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= (int) $u['is_active'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <?= (int) $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
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
