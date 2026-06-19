<?php
$title = 'Matches';
ob_start();
?>
<h1 class="h3 mb-4">Matches</h1>

<?php if (!$tournament): ?>
    <div class="alert alert-warning">
        Create and activate a tournament with teams before importing matches.
        <a href="<?= url('/admin/tournament') ?>">Go to Tournament setup</a>
    </div>
<?php else: ?>
    <p class="text-muted mb-3">
        Active tournament: <strong><?= e($tournament['name']) ?></strong>
        — <?= count($teams) ?> team(s) loaded
        <br><span class="small">Kickoff times use <?= e(app_timezone()) ?> (bets lock at kickoff in this zone).</span>
    </p>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong>Import matches from CSV</strong>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Upload a <code>.csv</code> file. Teams are matched by
                <strong>FIFA code</strong> (same codes used when importing teams).
            </p>
            <p class="small mb-3">
                Required columns:
                <code>home_fifa</code>,
                <code>away_fifa</code>,
                <code>kickoff_at</code>,
                <code>stage</code>.
                Optional: <code>group_name</code>, <code>venue</code> (or <code>stadium</code>), <code>country</code>.
            </p>
            <form method="POST"
                  action="<?= url('/admin/matches/import') ?>"
                  enctype="multipart/form-data"
                  class="row g-3 align-items-end">
                <?= \App\Services\Csrf::field() ?>
                <input type="hidden" name="tournament_id" value="<?= (int) $tournament['id'] ?>">
                <div class="col-md-8">
                    <label class="form-label">CSV file</label>
                    <input type="file"
                           name="matches_csv"
                           class="form-control"
                           accept=".csv,text/csv"
                           required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        Upload &amp; import
                    </button>
                </div>
            </form>
            <p class="small mt-3 mb-0">
                <a href="<?= url('/samples/matches_template.csv') ?>" download>
                    Download sample CSV template
                </a>
            </p>
        </div>
    </div>

    <div class="card shadow-sm mb-3 border-light">
        <div class="card-body small">
            <strong>Stage values</strong> (column <code>stage</code>):
            <code>group</code>,
            <code>round_of_16</code>,
            <code>quarter_final</code>,
            <code>semi_final</code>,
            <code>third_place</code>,
            <code>final</code>
            <br>
            <strong>Kickoff</strong> examples:
            <code>2026-06-15 14:00:00</code> or
            <code>2026-06-15T14:00</code>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped bg-white shadow-sm">
            <thead>
                <tr>
                    <th>Match</th>
                    <th>Stage</th>
                    <th>Group</th>
                    <th>Date &amp; venue</th>
                    <th>Status</th>
                    <th>Predictions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $m): ?>
                    <?php
                    $kickoff = date_create($m['kickoff_at'], timezone_open(app_timezone()));
                    $kickoffValue = $kickoff ? $kickoff->format('Y-m-d\TH:i') : '';
                    ?>
                    <tr>
                        <td><?= e($m['home_short_name']) ?> vs <?= e($m['away_short_name']) ?></td>
                        <td><?= e(str_replace('_', ' ', $m['stage'])) ?></td>
                        <td><?= e($m['group_name']) ?></td>
                        <td style="min-width: 28rem;">
                            <form method="POST"
                                  action="<?= url('/admin/matches/update') ?>"
                                  class="row g-2 align-items-end">
                                <?= \App\Services\Csrf::field() ?>
                                <input type="hidden" name="match_id" value="<?= (int) $m['id'] ?>">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Kickoff</label>
                                    <input type="datetime-local"
                                           name="kickoff_at"
                                           class="form-control form-control-sm"
                                           value="<?= e($kickoffValue) ?>"
                                           required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Venue</label>
                                    <input type="text"
                                           name="venue"
                                           class="form-control form-control-sm"
                                           value="<?= e($m['venue'] ?? '') ?>"
                                           placeholder="Stadium">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Country</label>
                                    <input type="text"
                                           name="venue_country"
                                           class="form-control form-control-sm"
                                           value="<?= e($m['venue_country'] ?? '') ?>"
                                           placeholder="Country">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td><?= e($m['status']) ?></td>
                        <td>
                            <form method="POST" action="<?= url('/admin/matches/toggle-predictions') ?>" class="d-inline">
                                <?= \App\Services\Csrf::field() ?>
                                <input type="hidden" name="match_id" value="<?= (int) $m['id'] ?>">
                                <input type="hidden" name="allow_predictions"
                                       value="<?= (int) $m['allow_predictions'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <?= (int) $m['allow_predictions'] ? 'Lock' : 'Unlock' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($matches)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No matches yet. Upload your CSV file above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
