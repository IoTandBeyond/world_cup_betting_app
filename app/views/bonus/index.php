<?php
$title = 'Bonus Bets';
ob_start();
?>
<h1 class="h3 mb-1">Bonus predictions</h1>
<p class="text-muted mb-4">One set per tournament — points awarded when the competition ends.</p>

<?php if (!$tournament): ?>
    <div class="empty-state">
        <i class="fa fa-star d-block"></i>
        <p>No active tournament.</p>
    </div>
<?php else: ?>
    <form method="POST" action="<?= url('/bonus') ?>">
        <?= \App\Services\Csrf::field() ?>

        <div class="row g-3">
            <div class="col-12">
                <div class="card bonus-card">
                    <div class="card-body">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-trophy text-warning me-1"></i>
                            World Cup winner
                            <span class="points-hint">+<?= (int) $points['winner'] ?> pts</span>
                        </label>
                        <select name="world_cup_winner_team_id" class="form-select" required>
                            <option value="">Select country</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"
                                    <?= ($bonus['world_cup_winner_team_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?> (<?= e($t['short_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php
            $playerFields = [
                [
                    'label' => 'Top scorer',
                    'points' => $points['scorer'],
                    'icon' => 'fa-futbol',
                    'position' => 'forward',
                    'player_key' => 'top_scorer_player_id',
                    'team_key' => 'top_scorer_team_id',
                    'name_key' => 'top_scorer_name',
                    'selected' => $bonus['top_scorer_player_id'] ?? null,
                ],
                [
                    'label' => 'Best goalkeeper',
                    'points' => $points['keeper'],
                    'icon' => 'fa-shield-halved',
                    'position' => 'goalkeeper',
                    'player_key' => 'best_goalkeeper_player_id',
                    'team_key' => 'best_goalkeeper_team_id',
                    'name_key' => 'best_goalkeeper_name',
                    'selected' => $bonus['best_goalkeeper_player_id'] ?? null,
                ],
                [
                    'label' => 'MVP',
                    'points' => $points['mvp'],
                    'icon' => 'fa-medal',
                    'position' => 'midfielder',
                    'player_key' => 'mvp_player_id',
                    'team_key' => 'mvp_team_id',
                    'name_key' => 'mvp_name',
                    'selected' => $bonus['mvp_player_id'] ?? null,
                ],
            ];
            ?>

            <?php foreach ($playerFields as $field): ?>
                <div class="col-md-4">
                    <div class="card bonus-card h-100">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                <i class="fa <?= e($field['icon']) ?> me-1"></i>
                                <?= e($field['label']) ?>
                                <span class="points-hint">+<?= (int) $field['points'] ?> pts</span>
                            </label>

                            <?php if (!empty($players)): ?>
                                <select name="<?= e($field['player_key']) ?>" class="form-select mb-2">
                                    <option value="">— Or pick from list —</option>
                                    <?php foreach ($players as $p): ?>
                                        <?php if ($field['position'] === 'goalkeeper' && $p['position'] !== 'goalkeeper') {
                                            continue;
                                        } ?>
                                        <?php if ($field['label'] === 'Top scorer' && $p['position'] === 'goalkeeper') {
                                            continue;
                                        } ?>
                                        <option value="<?= (int) $p['id'] ?>"
                                            <?= (int) ($field['selected'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                                            <?= e($p['name']) ?> (<?= e($p['team_short']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="small text-muted mb-2">or enter manually:</p>
                            <?php endif; ?>

                            <select name="<?= e($field['team_key']) ?>" class="form-select form-select-sm mb-2">
                                <option value="">Team</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="<?= e($field['name_key']) ?>"
                                   class="form-control form-control-sm"
                                   placeholder="Player name"
                                   maxlength="150">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-pool btn-lg">Save bonus predictions</button>
            <a href="<?= url('/dashboard') ?>" class="btn btn-outline-secondary btn-lg">Back to matches</a>
        </div>
    </form>
<?php endif; ?>
<?php
$content = ob_get_clean();
$user = $user ?? null;
require BASE_PATH . '/app/views/layouts/app.php';
