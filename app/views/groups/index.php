<?php
$title = 'Groups';
ob_start();
?>
<h1 class="h3 mb-1">Group standings</h1>

<?php if (!$tournament): ?>
    <div class="empty-state mt-4">
        <i class="fa fa-table d-block"></i>
        <p>No active tournament.</p>
    </div>
<?php elseif ($groups === []): ?>
    <p class="text-muted mb-4"><?= e($tournament['name']) ?> · <?= (int) $tournament['year'] ?></p>
    <div class="empty-state">
        <i class="fa fa-table d-block"></i>
        <p>No group-stage matches yet. Standings appear once group fixtures are imported.</p>
    </div>
<?php else: ?>
    <p class="text-muted mb-4">
        <?= e($tournament['name']) ?> · <?= (int) $tournament['year'] ?>
        — updated from match results (3 pts win, 1 pt draw)
    </p>

    <div class="row g-4">
        <?php foreach ($groups as $groupName => $rows): ?>
            <div class="col-12 col-lg-6 col-xl-4">
                <div class="group-standings-card">
                    <div class="group-standings-card__head">
                        Group <?= e($groupName) ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm group-standings-table mb-0">
                            <thead>
                                <tr>
                                    <th class="group-standings-table__team">Team</th>
                                    <th class="text-center" title="Played">P</th>
                                    <th class="text-center" title="Won">W</th>
                                    <th class="text-center" title="Drawn">D</th>
                                    <th class="text-center" title="Lost">L</th>
                                    <th class="text-center" title="Goals for">GF</th>
                                    <th class="text-center" title="Goals against">GA</th>
                                    <th class="text-center" title="Goal difference">GD</th>
                                    <th class="text-center" title="Points">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $index => $row): ?>
                                    <tr>
                                        <td class="group-standings-table__team">
                                            <span class="group-standings-table__pos"><?= $index + 1 ?></span>
                                            <img src="<?= e(team_flag_url($row['fifa_code'], $row['flag_url'])) ?>"
                                                 alt=""
                                                 width="24" height="16"
                                                 loading="lazy"
                                                 class="group-standings-table__flag">
                                            <span class="group-standings-table__code"><?= e($row['short_name']) ?></span>
                                            <span class="group-standings-table__name d-none d-md-inline">
                                                <?= e($row['name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= (int) $row['played'] ?></td>
                                        <td class="text-center"><?= (int) $row['won'] ?></td>
                                        <td class="text-center"><?= (int) $row['drawn'] ?></td>
                                        <td class="text-center"><?= (int) $row['lost'] ?></td>
                                        <td class="text-center"><?= (int) $row['goals_for'] ?></td>
                                        <td class="text-center"><?= (int) $row['goals_against'] ?></td>
                                        <td class="text-center<?= $row['goal_diff'] > 0 ? ' text-success' : ($row['goal_diff'] < 0 ? ' text-danger' : '') ?>">
                                            <?= $row['goal_diff'] > 0 ? '+' : '' ?><?= (int) $row['goal_diff'] ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= (int) $row['points'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/views/layouts/app.php';
