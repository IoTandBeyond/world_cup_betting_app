<?php
$title = 'Leaderboard';
$user = \App\Services\Auth::user();
$userRank = $userRank ?? null;
$userPoints = $userPoints ?? 0;
ob_start();
?>
<h1 class="h3 mb-4">Leaderboard</h1>

<?php if (!$tournament): ?>
    <div class="empty-state">
        <i class="fa fa-ranking-star d-block"></i>
        <p>No active tournament.</p>
    </div>
<?php else: ?>
    <?php if ($userRank): ?>
        <div class="leaderboard-hero">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rank-big">#<?= (int) $userRank ?></div>
                </div>
                <div class="col">
                    <div class="opacity-75 small text-uppercase">Your position</div>
                    <div class="fs-4 fw-bold"><?= e($user['name']) ?></div>
                    <div class="mt-1">
                        <span class="badge bg-warning text-dark fs-6"><?= (int) $userPoints ?> points</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <p class="text-muted mb-3"><?= e($tournament['name']) ?> — overall standings</p>

    <div class="table-responsive">
        <table class="table table-hover bg-white shadow-sm rounded overflow-hidden mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:4rem">#</th>
                    <th>Player</th>
                    <th class="text-end">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rankings)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            No scores yet — place bets to appear here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rankings as $row): ?>
                        <?php
                        $isMe = (int) $row['user_id'] === (int) $user['id'];
                        $rank = (int) $row['rank_position'];
                        $medal = match ($rank) {
                            1 => '🥇',
                            2 => '🥈',
                            3 => '🥉',
                            default => '',
                        };
                        ?>
                        <tr<?= $isMe ? ' class="table-success fw-semibold"' : '' ?>>
                            <td>
                                <span class="rank-medal"><?= $medal ?></span>
                                <?= $rank ?>
                            </td>
                            <td>
                                <?= e($row['name']) ?>
                                <?php if ($isMe): ?>
                                    <span class="badge bg-success ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <strong><?= (int) $row['total_points'] ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card bonus-card mt-4">
        <div class="card-body small">
            <strong>Scoring reminder</strong>
            <ul class="mb-0 mt-2">
                <li>Exact score: 5 points</li>
                <li>Correct winner or draw (wrong score): 3 points</li>
                <li>Bonus bets: see <a href="<?= url('/bonus') ?>">Bonus page</a></li>
            </ul>
        </div>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/views/layouts/app.php';
