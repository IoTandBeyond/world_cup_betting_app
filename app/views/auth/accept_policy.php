<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#094d2a">
    <title>Rules &amp; Policy — World Cup Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('css/player.css') ?>">
</head>
<body class="player-app policy-page">
<header class="app-header">
    <nav class="navbar navbar-dark bg-pool-nav">
        <div class="container">
            <span class="navbar-brand mb-0">
                <i class="fa fa-trophy text-warning me-1"></i> World Cup Pool
            </span>
        </div>
    </nav>
</header>

<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h4 mb-2">Rules &amp; Participant Policy</h1>
            <p class="text-muted small mb-3">
                Version <?= e($policyVersion) ?> — Please read carefully before continuing.
                Hi <?= e($user['name']) ?>, you must accept these terms to use the pool.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm policy-scroll mb-3">
                <div class="card-body policy-document small">
                    <h2 class="h6">1. Purpose</h2>
                    <p class="mb-2">
                        The World Cup Pool is a <strong>private, invitation-only</strong> prediction game for the FIFA World Cup. It is
                        intended for fun and friendly competition among invited participants only. It is <strong>NOT</strong> a public
                        gambling service and does not offer cash prizes unless explicitly announced in writing by the organizer.    
                    </p>
                    
                    <h2 class="h6">2. Eligibility</h2>
                    <ul>
                        <li>Participation is <strong>by invitation only</strong></li>
                        <li>You must receive an email invite from the organizer and complete account setup.</li>
                        <li>You must use the email address that was invited.</li>
                        <li>One account per person.</li>
                        <li>You will set a personal password after accepting this policy.</li>
                    </ul>

                     <h2 class="h6">3. How to participate</h2>
                    <ul>
                        <li>Login with the email address that was invited and password created.</li>
                        <li>Go to <strong>My Bets</strong> to view matches and enter predictions.</li>
                        <li>Optionally complete <strong>Bonus</strong> predictions (tournament winner, top scorer, etc.).</li>
                        <li>Check the <strong>Leaderboard</strong> for overall standings.</li>
                    </ul>

                    <h2 class="h6">4. Match predictions</h2>
                    <p>Enter exact score (home and away goals) before kickoff.</p>
                    <table class="table table-sm table-bordered">
                        <tr><th>Result</th><th>Points</th></tr>
                        <tr><td>Exact score</td><td><strong>5</strong></td></tr>
                        <tr><td>Correct winner or draw + correct goal difference</td><td><strong>3</strong></td></tr>
                        <tr><td>Correct winner or draw only</td><td><strong>2</strong></td></tr>
                        <tr><td>Wrong outcome</td><td><strong>0</strong></td></tr>
                    </table>

                    <h2 class="h6">5. Bonus predictions</h2>
                    <p>Each participant may submit <strong>one set</strong> of bonus picks for the active tournament:</p>
                    <ul class="mb-0">
                        <li>World Cup winner — 10 pts</li>
                        <li>Top scorer — 10 pts</li>
                        <li>Best goalkeeper — 7 pts</li>
                        <li>MVP — 7 pts</li>
                    </ul>
                    <br>
                    <p>Bonus points are awarded when the organizer confirms official tournament results and runs scoring. Bonus entry closes at the <strong>first match kickoff</strong> of the tournament.</p>

                    <h2 class="h6">6. Leaderboard and ranking</h2>
                    <ul class="mb-0">
                        <li>Standings show <strong>total points</strong> (match predictions + bonus predictions).</li>
                        <li>Rankings are <strong>overall</strong> for the active tournament.</li>
                        <li>In case of a tie, the organizer may apply additional tie-break rules (not shown in the app) or leave participants tied.</li>
                        <li>The leaderboard is updated after match results are entered and scored.</li>
                    </ul>

                    <h2 class="h6">7. Acceptable use</h2>
                    <ul class="mb-0">
                        <li>Keep your login credentials <strong>confidential</strong>.</li>
                        <li>Not attempt to access other users’ accounts or admin areas.</li>
                        <li>Not interfere with the website, database, or scoring system.</li>
                        <li>Not use bots or automated tools to submit predictions.</li>
                    </ul>

                    <h2 class="h6">8. Deadlines</h2>
                    <ul>
                        <li>Predictions must be submitted <strong>before kickoff</strong>.</li>
                        <li>After kickoff, or if the organizer locks a match, predictions are <strong>closed</strong> for that game.</li>
                        <li>You may <strong>change</strong> your prediction until the match is locked or kickoff time passes.</li>
                    </ul>

                    <h2 class="h6 mt-3">9. Important disclaimer</h2>
                    <p class="mb-2">
                        The application is provided <strong>“as is”</strong>. The organizer does
                        <strong>not</strong> guarantee availability, accuracy, or correct scoring at all times.
                    </p>
                    <p class="mb-2">
                        <strong>If any problem arises with the app</strong> (downtime, errors, lost
                        predictions, wrong points, email issues, etc.), the organizer is
                        <strong>not liable</strong>. You accept the risk of using this digital system.
                        <strong>No compensation</strong> is owed unless the organizer agrees in writing.
                    </p>
                    <p class="mb-0">Administrator decisions are final.</p>

                    <!-- <p class="mt-3 mb-0 text-muted">
                        Full document:
                        <code>docs/PARTICIPANT_RULES_AND_POLICY.md</code> (version <?= e($policyVersion) ?>).
                    </p> -->
                </div>
            </div>

            <div class="card shadow-sm policy-accept-bar">
                <div class="card-body">
                    <form method="POST" action="<?= url('/policy/accept') ?>">
                        <?= \App\Services\Csrf::field() ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox"
                                   name="accept_policy" value="1"
                                   id="accept_policy" required>
                            <label class="form-check-label" for="accept_policy">
                                I have read and <strong>accept</strong> the Rules &amp; Participant
                                Policy (version <?= e($policyVersion) ?>). I understand that I
                                cannot use the application without accepting, and that technical
                                problems do not entitle me to adjusted scores or compensation.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-pool w-100 btn-lg">
                            Accept and continue
                        </button>
                    </form>
                    <p class="text-center mt-3 mb-0">
                        <a href="<?= url('/logout') ?>" class="small text-muted">Log out</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
