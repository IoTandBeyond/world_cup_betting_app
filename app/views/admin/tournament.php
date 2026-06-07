<?php
$title = 'Tournament & Teams';
$isSuperAdmin = $isSuperAdmin ?? true;
$isHost = $isHost ?? false;
ob_start();
?>
<h1 class="h3 mb-4"><?= $isHost ? 'My tournament' : 'Tournament &amp; Teams' ?></h1>

<div class="row g-4">
    <?php if ($isSuperAdmin): ?>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>1. Create tournament</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/tournament') ?>">
                    <?= \App\Services\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="Office World Cup 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-muted">(optional)</span></label>
                        <input type="text" name="slug" class="form-control"
                               placeholder="office-world-cup-2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control"
                               value="2026" min="2000" max="2100" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <hr>
                    <p class="small text-muted mb-2">Tournament host (manages teams, matches, invitations)</p>
                    <div class="mb-3">
                        <label class="form-label">Host name</label>
                        <input type="text" name="host_name" class="form-control" placeholder="Jane Smith" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host email</label>
                        <input type="email" name="host_email" class="form-control"
                               placeholder="host@example.com" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="set_active" value="1"
                               class="form-check-input" id="set_active" checked>
                        <label class="form-check-label" for="set_active">
                            Activate tournament immediately
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create tournament &amp; invite host</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white">
                <strong>All tournaments</strong>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($tournaments as $t): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= url('/admin/tournament?id=' . (int) $t['id']) ?>">
                            <?= e($t['name']) ?> (<?= (int) $t['year'] ?>)
                        </a>
                        <span>
                            <span class="badge bg-secondary me-1"><?= (int) $t['team_count'] ?> teams</span>
                            <span class="badge bg-<?= $t['status'] === 'active' ? 'success' : 'light text-dark' ?>">
                                <?= e($t['status']) ?>
                            </span>
                        </span>
                    </li>
                    <?php if (!empty($t['host_email'])): ?>
                        <li class="list-group-item small text-muted py-1 ps-4">
                            Host: <?= e($t['host_name'] ?? '') ?> &lt;<?= e($t['host_email']) ?>&gt;
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($tournaments)): ?>
                    <li class="list-group-item text-muted">No tournaments yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="<?= $isSuperAdmin ? 'col-lg-7' : 'col-12' ?>">
        <?php if (!$selected): ?>
            <div class="alert alert-info">
                Create a tournament, then select it from the list to add teams.
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h5 mb-0"><?= e($selected['name']) ?></h2>
                <?php if ($isSuperAdmin && $selected['status'] !== 'active'): ?>
                    <form method="POST" action="<?= url('/admin/tournament/activate') ?>" class="d-inline">
                        <?= \App\Services\Csrf::field() ?>
                        <input type="hidden" name="tournament_id" value="<?= (int) $selected['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm">Activate</button>
                    </form>
                <?php elseif ($selected['status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?= e($selected['status']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($selected['host_email'])): ?>
                <p class="small text-muted">Host: <?= e($selected['host_name'] ?? '') ?>
                    &lt;<?= e($selected['host_email']) ?>&gt;</p>
            <?php endif; ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>2. Add one team</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/tournament/teams') ?>" class="row g-2">
                        <?= \App\Services\Csrf::field() ?>
                        <input type="hidden" name="tournament_id" value="<?= (int) $selected['id'] ?>">
                        <div class="col-md-5">
                            <input type="text" name="name" class="form-control" placeholder="Brazil" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="short_name" class="form-control" placeholder="ARG" maxlength="10" required title="3-letter display code">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="fifa_code" class="form-control" placeholder="AR" maxlength="10" required title="2-letter ISO code for flags">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary w-100">Add team</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>3. Bulk import teams (CSV)</strong>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        One team per line: <code>Name,short_name,fifa_code</code>
                        (e.g. <code>Argentina,ARG,AR</code> — 3-letter short, 2-letter ISO for flags).
                    </p>
                    <form method="POST" action="<?= url('/admin/tournament/import') ?>">
                        <?= \App\Services\Csrf::field() ?>
                        <input type="hidden" name="tournament_id" value="<?= (int) $selected['id'] ?>">
                        <textarea name="teams_csv" class="form-control font-monospace mb-2" rows="8"
                                  placeholder="Brazil,BRA,BR&#10;Argentina,ARG,AR&#10;Chile,CHL,CL"></textarea>
                        <button type="submit" class="btn btn-primary">Import teams</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <strong>Teams (<?= count($teams) ?>)</strong>
                    <a href="<?= url('/admin/matches') ?>" class="small">Next: add matches →</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Short</th>
                                <th>ISO (flag)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?= e($team['name']) ?></td>
                                    <td><?= e($team['short_name']) ?></td>
                                    <td><?= e($team['fifa_code']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($teams)): ?>
                                <tr><td colspan="3" class="text-muted text-center">No teams yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<div class="card shadow-sm mt-4 border-info">
    <div class="card-header bg-info-subtle">
        <strong>SQL procedure (optional)</strong>
    </div>
    <div class="card-body small">
        <p>Install stored procedures once:</p>
        <pre class="bg-dark text-light p-2 rounded mb-2">mysql -u root -p world_cup_poll_db &lt; db/procedures.sql</pre>
        <p class="mb-1">Create tournament:</p>
        <pre class="bg-light p-2 rounded mb-2">CALL sp_create_tournament(
  'FIFA World Cup 2026', 'world-cup-2026', 2026,
  '2026-06-11', '2026-07-19', 'active'
);
CALL sp_activate_tournament(1);</pre>
        <p class="mb-1">Add team:</p>
        <pre class="bg-light p-2 rounded mb-2">CALL sp_add_team(1, 'Brazil', 'BRA', 'BRA', NULL);</pre>
        <p class="mb-0">Example seed file: <code>db/seeds/world_cup_2026_teams.sql</code></p>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
