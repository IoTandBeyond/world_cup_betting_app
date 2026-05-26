<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Invitation;
use App\Models\MatchModel;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\InvitationService;
use App\Services\ScoringService;
use App\Services\MatchImportService;
use App\Services\TournamentSetupService;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $stats = [
            'users' => User::count(),
            'matches' => MatchModel::count(),
            'pending_matches' => MatchModel::pendingCount(),
        ];

        $this->view('admin/dashboard', compact('stats'));
    }

    public function invitations(): void
    {
        $this->view('admin/invitations', [
            'invitations' => Invitation::all(),
        ]);
    }

    public function storeInvitation(): void
    {
        Csrf::validateOrAbort();

        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $admin = Auth::user();

        if (!Validator::email($email)) {
            Flash::set('error', 'Enter a valid email address.');
            $this->redirect('/admin/invitations');
        }

        try {
            $result = InvitationService::sendInvitation(
                $email,
                (int) $admin['id'],
                $name !== '' ? $name : null
            );

            Flash::set(
                'success',
                'Invitation email sent to ' . $result['email']
                . ' from no-reply@iot4b.ca with a temporary password.'
            );
        } catch (\InvalidArgumentException $e) {
            Flash::set('error', $e->getMessage());
        } catch (\Throwable $e) {
            Flash::set(
                'error',
                'Could not send email: ' . $e->getMessage()
                . ' Check MAIL_* settings in .env.'
            );
        }

        $this->redirect('/admin/invitations');
    }

    public function users(): void
    {
        $this->view('admin/users', [
            'users' => User::all(),
        ]);
    }

    public function toggleUser(): void
    {
        Csrf::validateOrAbort();

        $id = (int) ($_POST['user_id'] ?? 0);
        $active = (int) ($_POST['is_active'] ?? 0) === 1;

        if ($id && $id !== (int) Auth::user()['id']) {
            User::setActive($id, $active);
            Flash::set('success', 'User updated.');
        }

        $this->redirect('/admin/users');
    }

    public function resendTemporaryPassword(): void
    {
        Csrf::validateOrAbort();

        $id = (int) ($_POST['user_id'] ?? 0);
        $admin = Auth::user();

        if (!$id || $id === (int) $admin['id']) {
            Flash::set('error', 'Invalid user.');
            $this->redirect('/admin/users');
        }

        try {
            $result = InvitationService::resendTemporaryPassword(
                $id,
                (int) $admin['id']
            );

            Flash::set(
                'success',
                'New temporary password emailed to ' . $result['email'] . '.'
            );
        } catch (\InvalidArgumentException $e) {
            Flash::set('error', $e->getMessage());
        } catch (\Throwable $e) {
            Flash::set(
                'error',
                'Could not send email: ' . $e->getMessage()
                . ' Check MAIL_* settings in .env.'
            );
        }

        $this->redirect('/admin/users');
    }

    public function matches(): void
    {
        $tournament = Tournament::active();
        $matches = [];
        $teams = [];

        if ($tournament) {
            $matches = MatchModel::forTournament((int) $tournament['id']);
            $teams = Team::forTournament((int) $tournament['id']);
        }

        $this->view('admin/matches', [
            'tournament' => $tournament,
            'matches' => $matches,
            'teams' => $teams,
        ]);
    }

    public function importMatches(): void
    {
        Csrf::validateOrAbort();

        $tournament = Tournament::active();

        if (!$tournament) {
            Flash::set('error', 'No active tournament. Create and activate one first.');
            $this->redirect('/admin/matches');
        }

        $file = $_FILES['matches_csv'] ?? null;

        if (
            !$file
            || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            Flash::set('error', 'Please choose a CSV file to upload.');
            $this->redirect('/admin/matches');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            Flash::set('error', 'Only .csv files are allowed.');
            $this->redirect('/admin/matches');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Flash::set('error', 'CSV file must be 2 MB or smaller.');
            $this->redirect('/admin/matches');
        }

        $content = file_get_contents($file['tmp_name']);

        if ($content === false || trim($content) === '') {
            Flash::set('error', 'CSV file is empty or could not be read.');
            $this->redirect('/admin/matches');
        }

        $result = MatchImportService::import(
            (int) $tournament['id'],
            $content
        );

        $msg = "{$result['imported']} match(es) imported.";

        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} duplicate(s) skipped.";
        }

        if (!empty($result['errors'])) {
            $preview = implode(' ', array_slice($result['errors'], 0, 5));

            if (count($result['errors']) > 5) {
                $preview .= ' …';
            }

            Flash::set('error', $preview);
        }

        if ($result['imported'] > 0) {
            Flash::set('success', $msg);
        } elseif (empty($result['errors'])) {
            Flash::set('error', 'No matches were imported.');
        }

        $this->redirect('/admin/matches');
    }

    public function results(): void
    {
        $tournament = Tournament::active();
        $matches = $tournament
            ? MatchModel::forTournament((int) $tournament['id'])
            : [];

        $this->view('admin/results', [
            'tournament' => $tournament,
            'matches' => $matches,
        ]);
    }

    public function saveResult(): void
    {
        Csrf::validateOrAbort();

        $matchId = (int) ($_POST['match_id'] ?? 0);
        $home = (int) ($_POST['home_score'] ?? 0);
        $away = (int) ($_POST['away_score'] ?? 0);

        MatchModel::updateScore($matchId, $home, $away);
        $scored = ScoringService::scoreMatch($matchId);

        Flash::set(
            'success',
            "Result saved. {$scored} prediction(s) scored."
        );

        $this->redirect('/admin/results');
    }

    public function togglePredictions(): void
    {
        Csrf::validateOrAbort();

        $matchId = (int) ($_POST['match_id'] ?? 0);
        $allowed = (int) ($_POST['allow_predictions'] ?? 0) === 1;

        MatchModel::setAllowPredictions($matchId, $allowed);

        Flash::set('success', 'Prediction lock updated.');
        $this->redirect('/admin/matches');
    }

    public function tournament(): void
    {
        $tournaments = Tournament::all();
        $active = Tournament::active();
        $selectedId = (int) ($_GET['id'] ?? ($active['id'] ?? ($tournaments[0]['id'] ?? 0)));
        $selected = $selectedId ? Tournament::findById($selectedId) : null;
        $teams = $selected ? Team::forTournament($selectedId) : [];

        $this->view('admin/tournament', [
            'tournaments' => $tournaments,
            'active' => $active,
            'selected' => $selected,
            'teams' => $teams,
        ]);
    }

    public function storeTournament(): void
    {
        Csrf::validateOrAbort();

        $name = trim($_POST['name'] ?? '');
        $year = (int) ($_POST['year'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';

        if (!Validator::required($name) || $year < 2000 || !$start || !$end) {
            Flash::set('error', 'Fill in tournament name, year, and dates.');
            $this->redirect('/admin/tournament');
        }

        $status = ($_POST['set_active'] ?? '') === '1' ? 'active' : 'upcoming';

        $id = TournamentSetupService::createTournament([
            'name' => $name,
            'slug' => trim($_POST['slug'] ?? ''),
            'year' => $year,
            'start_date' => $start,
            'end_date' => $end,
            'status' => $status,
        ]);

        if ($status === 'active') {
            TournamentSetupService::activate($id);
        }

        Flash::set('success', 'Tournament created.');
        $this->redirect('/admin/tournament?id=' . $id);
    }

    public function activateTournament(): void
    {
        Csrf::validateOrAbort();

        $id = (int) ($_POST['tournament_id'] ?? 0);

        if ($id) {
            TournamentSetupService::activate($id);
            Flash::set('success', 'Tournament activated for predictions and matches.');
        }

        $this->redirect('/admin/tournament?id=' . $id);
    }

    public function storeTeam(): void
    {
        Csrf::validateOrAbort();

        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $short = trim($_POST['short_name'] ?? '');
        $fifa = trim($_POST['fifa_code'] ?? '');

        if (!$tournamentId || !Validator::required($name) || !Validator::required($short) || !Validator::required($fifa)) {
            Flash::set('error', 'Team name, short name, and FIFA code are required.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        try {
            Team::callAddProcedure($tournamentId, $name, $short, $fifa);
        } catch (\PDOException) {
            Team::create([
                'tournament_id' => $tournamentId,
                'name' => $name,
                'short_name' => $short,
                'fifa_code' => $fifa,
            ]);
        }

        Flash::set('success', 'Team added.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function importTeams(): void
    {
        Csrf::validateOrAbort();

        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);
        $csv = trim($_POST['teams_csv'] ?? '');

        if (!$tournamentId || $csv === '') {
            Flash::set('error', 'Paste team rows to import.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $result = TournamentSetupService::importTeams($tournamentId, $csv);

        $msg = "{$result['imported']} team(s) imported.";

        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} duplicate(s) skipped.";
        }

        if (!empty($result['errors'])) {
            $msg .= ' Issues: ' . implode('; ', array_slice($result['errors'], 0, 3));
        }

        Flash::set('success', $msg);
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    private static function normalizeKickoff(string $value): string
    {
        if ($value === '') {
            return date('Y-m-d H:i:s');
        }

        return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
    }
}
