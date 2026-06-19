<?php
$title = 'Results';
ob_start();
?>
<h1 class="h3 mb-4">Enter results</h1>

<?php if (!$tournament): ?>
    <div class="alert alert-warning">No active tournament.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($matches as $m): ?>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="small text-muted mb-2">
                            <span class="badge bg-secondary me-1">
                                <?= e(match_stage_label($m['stage'])) ?>
                            </span>
                            <?php if (!empty($m['group_name'])): ?>
                                <span class="badge bg-light text-dark border">
                                    Group <?= e($m['group_name']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($m['status'] === 'finished'): ?>
                                <span class="badge bg-success">Finished</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?= e($m['status']) ?></span>
                            <?php endif; ?>
                        </p>
                        <h5 class="mb-2"><?= e($m['home_team_name']) ?> vs <?= e($m['away_team_name']) ?></h5>
                        <p class="small text-muted mb-3">
                            <i class="fa-regular fa-clock me-1"></i><?= e($m['kickoff_at']) ?>
                        </p>
                        <form method="POST" action="<?= url('/admin/results') ?>" class="row g-2 align-items-end">
                            <?= \App\Services\Csrf::field() ?>
                            <input type="hidden" name="match_id" value="<?= (int) $m['id'] ?>">
                            <div class="col-4">
                                <label class="form-label small">Home</label>
                                <input type="number"
                                       name="home_score"
                                       class="form-control"
                                       min="0"
                                       max="20"
                                       required
                                       value="<?= $m['home_score'] !== null ? (int) $m['home_score'] : '' ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Away</label>
                                <input type="number"
                                       name="away_score"
                                       class="form-control"
                                       min="0"
                                       max="20"
                                       required
                                       value="<?= $m['away_score'] !== null ? (int) $m['away_score'] : '' ?>">
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-success w-100">
                                    <?= $m['status'] === 'finished' ? 'Update score' : 'Save &amp; score' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($matches)): ?>
            <div class="col-12">
                <div class="alert alert-info">No matches yet. Import fixtures first.</div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
