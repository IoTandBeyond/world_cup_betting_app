<?php
/** @var array $match */
/** @var array|null $pred */
$pred = $pred ?? null;
$locked = !\App\Models\MatchModel::canPredict($match);
$finished = $match['status'] === 'finished';
$cardClass = 'match-card';
if ($finished) {
    $cardClass .= ' match-card--finished';
} elseif ($locked) {
    $cardClass .= ' match-card--locked';
}
?>
<div class="col-12 col-lg-6">
    <article class="<?= e($cardClass) ?>">
        <div class="match-card__head">
            <span>
                <?php if (!empty($match['group_name'])): ?>
                    Group <?= e($match['group_name']) ?> ·
                <?php endif; ?>
                <?= e(match_stage_label($match['stage'])) ?>
            </span>
            <span>
                <i class="fa-regular fa-clock me-1"></i>
                <?= e(format_kickoff($match['kickoff_at'])) ?>
            </span>
        </div>
        <div class="match-card__teams">
            <div class="match-card__team">
                <img src="<?= e(team_flag_url($match['home_fifa_code'], $match['home_flag_url'] ?? null)) ?>"
                     alt="<?= e($match['home_short_name']) ?>"
                     loading="lazy"
                     width="48" height="32">
                <div class="code"><?= e($match['home_short_name']) ?></div>
                <span class="name"><?= e($match['home_team_name']) ?></span>
            </div>
            <div class="match-card__vs">VS</div>
            <div class="match-card__team">
                <img src="<?= e(team_flag_url($match['away_fifa_code'], $match['away_flag_url'] ?? null)) ?>"
                     alt="<?= e($match['away_short_name']) ?>"
                     loading="lazy"
                     width="48" height="32">
                <div class="code"><?= e($match['away_short_name']) ?></div>
                <span class="name"><?= e($match['away_team_name']) ?></span>
            </div>
        </div>
        <div class="match-card__body">
            <?php if ($finished): ?>
                <div class="result-line text-center mb-2">
                    Final: <strong><?= (int) $match['home_score'] ?> – <?= (int) $match['away_score'] ?></strong>
                </div>
                <?php if ($pred): ?>
                    <p class="text-center mb-1 small text-muted">
                        Your pick: <?= (int) $pred['predicted_home_score'] ?>–<?= (int) $pred['predicted_away_score'] ?>
                    </p>
                    <p class="text-center mb-0">
                        <span class="badge points-badge rounded-pill">
                            +<?= (int) $pred['points_awarded'] ?> pts
                        </span>
                    </p>
                <?php else: ?>
                    <p class="text-center small text-muted mb-0">No prediction placed</p>
                <?php endif; ?>
            <?php elseif ($locked): ?>
                <div class="bet-slip text-center">
                    <div class="bet-slip-label"><i class="fa fa-lock me-1"></i> Predictions locked</div>
                    <?php if ($pred): ?>
                        <p class="mb-0 fw-semibold">
                            Your pick: <?= (int) $pred['predicted_home_score'] ?> – <?= (int) $pred['predicted_away_score'] ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-0 small text-muted">You did not submit a prediction</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bet-slip">
                    <div class="bet-slip-label"><i class="fa fa-ticket me-1"></i> Your bet slip</div>
                    <form method="POST" action="<?= url('/predictions') ?>">
                        <?= \App\Services\Csrf::field() ?>
                        <input type="hidden" name="match_id" value="<?= (int) $match['id'] ?>">
                        <input type="hidden" name="return_tab" value="<?= e($tab ?? 'upcoming') ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <label class="form-label small mb-1"><?= e($match['home_short_name']) ?></label>
                                <input type="number" name="predicted_home_score"
                                       class="form-control score-input"
                                       min="0" max="20" required
                                       inputmode="numeric"
                                       value="<?= $pred ? (int) $pred['predicted_home_score'] : '' ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1"><?= e($match['away_short_name']) ?></label>
                                <input type="number" name="predicted_away_score"
                                       class="form-control score-input"
                                       min="0" max="20" required
                                       inputmode="numeric"
                                       value="<?= $pred ? (int) $pred['predicted_away_score'] : '' ?>">
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-pool w-100">
                                    <?= $pred ? 'Update' : 'Place bet' ?>
                                </button>
                            </div>
                        </div>
                        <p class="small text-muted mt-2 mb-0 text-center">
                            Exact score = 5 pts · Winner or draw = 3 pts
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </article>
</div>
