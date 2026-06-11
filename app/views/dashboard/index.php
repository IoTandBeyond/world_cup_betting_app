<?php
$title = 'My Bets';
$tab = $tab ?? 'upcoming';
ob_start();
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">My Bets</h1>
        <?php if ($tournament): ?>
            <p class="text-muted mb-0"><?= e($tournament['name']) ?> · <?= (int) $tournament['year'] ?></p>
        <?php endif; ?>
    </div>
    <?php if ($tournament && isset($rank) && $rank): ?>
        <a href="<?= url('/leaderboard') ?>" class="btn btn-outline-success btn-sm">
            Rank #<?= (int) $rank ?> · <?= (int) $points ?> pts
        </a>
    <?php endif; ?>
</div>

<?php if (!$tournament): ?>
    <div class="empty-state">
        <i class="fa fa-futbol d-block"></i>
        <p>No tournament is active yet. Check back soon.</p>
    </div>
<?php else: ?>
    <div class="stats-strip">
        <div class="stat-pill">
            <div class="value"><?= (int) $stats['to_pick'] ?></div>
            <div class="label">To pick</div>
        </div>
        <div class="stat-pill">
            <div class="value"><?= (int) $stats['picked'] ?></div>
            <div class="label">Upcoming picks</div>
        </div>
        <div class="stat-pill">
            <div class="value"><?= (int) $stats['finished'] ?></div>
            <div class="label">Played</div>
        </div>
        <div class="stat-pill">
            <div class="value"><?= (int) $points ?></div>
            <div class="label">Total points</div>
        </div>
    </div>

    <ul class="nav nav-tabs match-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link<?= $tab === 'upcoming' ? ' active' : '' ?>"
               href="<?= url('/dashboard?tab=upcoming') ?>">
                Upcoming
                <?php if ($stats['to_pick'] > 0): ?>
                    <span class="badge bg-danger ms-1"><?= (int) $stats['to_pick'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $tab === 'mine' ? ' active' : '' ?>"
               href="<?= url('/dashboard?tab=mine') ?>">
                My picks
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?= $tab === 'results' ? ' active' : '' ?>"
               href="<?= url('/dashboard?tab=results') ?>">
                Results
            </a>
        </li>
    </ul>

    <?php if (empty($matches)): ?>
        <div class="empty-state">
            <?php if ($tab === 'upcoming'): ?>
                <i class="fa fa-calendar d-block"></i>
                <p>No upcoming matches. Import fixtures in admin.</p>
            <?php elseif ($tab === 'mine'): ?>
                <i class="fa fa-clipboard-list d-block"></i>
                <p>You have not placed any predictions yet.</p>
                <a href="<?= url('/dashboard?tab=upcoming') ?>" class="btn btn-pool">Browse upcoming games</a>
            <?php else: ?>
                <i class="fa fa-flag-checkered d-block"></i>
                <p>No finished matches yet.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="match-search-bar mb-3">
            <label for="match-search" class="form-label small text-muted mb-1">
                Search by team, stadium, or country
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="search"
                       id="match-search"
                       class="form-control"
                       placeholder="e.g. Argentina, MetLife, USA"
                       autocomplete="off">
            </div>
            <p id="match-search-empty" class="small text-muted mt-2 mb-0 d-none">
                No matches match your search.
            </p>
        </div>
        <div class="row g-3" id="match-list">
            <?php foreach ($matches as $match): ?>
                <?php
                $pred = $predictions[(int) $match['id']] ?? null;
                require BASE_PATH . '/app/views/partials/match_card.php';
                ?>
            <?php endforeach; ?>
        </div>
        <script>
        (function () {
            var input = document.getElementById('match-search');
            var list = document.getElementById('match-list');
            var emptyMsg = document.getElementById('match-search-empty');
            if (!input || !list) {
                return;
            }
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                var visible = 0;
                list.querySelectorAll('.match-list-item').forEach(function (item) {
                    var hay = item.getAttribute('data-search') || '';
                    var show = q === '' || hay.indexOf(q) !== -1;
                    item.classList.toggle('d-none', !show);
                    if (show) {
                        visible++;
                    }
                });
                if (emptyMsg) {
                    emptyMsg.classList.toggle('d-none', q === '' || visible > 0);
                }
            });
        })();
        </script>
    <?php endif; ?>

    <div class="card bonus-card mt-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h6 mb-1"><i class="fa fa-star text-warning me-1"></i> Tournament bonus bets</h2>
                <p class="small text-muted mb-0">Winner, top scorer, goalkeeper &amp; MVP</p>
            </div>
            <a href="<?= url('/bonus') ?>" class="btn btn-pool">Bonus predictions</a>
        </div>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$user = $user ?? null;
require BASE_PATH . '/app/views/layouts/app.php';
