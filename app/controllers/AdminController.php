<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Invitation;
use App\Models\MatchModel;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMember;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\HostService;
use App\Services\InvitationService;
use App\Services\MatchImportService;
use App\Services\ScoringService;
use App\Services\TournamentAuth;
use App\Services\TournamentSetupService;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        TournamentAuth::requireSuperAdmin();

        $stats = [
            'users' => User::count(),
            'matches' => MatchModel::count(),
            'pending_matches' => MatchModel::pendingCount(),
            'tournaments' => count(Tournament::all()),
        ];

        $this->view('admin/dashboard', compact('stats'));
    }

    public function invitations(): void
    {
        $tournamentId = TournamentAuth::adminTournamentId();

        if (!$tournamentId) {
            Flash::set('error', 'No tournament assigned.');
            $this->redirect(Auth::isHost() ? '/admin/tournament' : '/admin/tournament');
        }

        $tournament = Tournament::findById($tournamentId);
        TournamentAuth::requireCanManageTournament($tournamentId);

        $this->view('admin/invitations', [
            'invitations' => Invitation::forTournament($tournamentId),
            'tournament' => $tournament,
            'isSuperAdmin' => Auth::isSuperAdmin(),
        ]);
    }

    public function storeInvitation(): void
    {
        Csrf::validateOrAbort();

        $tournamentId = TournamentAuth::adminTournamentId();

        if (!$tournamentId) {
            Flash::set('error', 'No tournament assigned.');
            $this->redirect('/admin/invitations');
        }

        TournamentAuth::requireCanManageTournament($tournamentId);

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
                $tournamentId,
                $name !== '' ? $name : null
            );

            $msg = !empty($result['existing_user'])
                ? 'Existing player added to the tournament: ' . $result['email']
                : 'Invitation email sent to ' . $result['email']
                    . ' with a temporary password.';

            Flash::set('success', $msg);
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
        if (Auth::isHost()) {
            $tournamentId = TournamentAuth::adminTournamentId();

            if (!$tournamentId) {
                Flash::set('error', 'No tournament assigned.');
                $this->redirect('/admin/tournament');
            }

            TournamentAuth::requireCanManageTournament($tournamentId);

            $tournament = Tournament::findById($tournamentId);
            $users = TournamentMember::usersForTournament($tournamentId);

            foreach ($users as &$user) {
                $user['tournament_label'] = $tournament['name']
                    . ' (' . (int) $tournament['year'] . ')';
            }
            unset($user);

            $this->view('admin/users', [
                'users' => $users,
                'tournament' => $tournament,
                'isSuperAdmin' => false,
                'isGlobalList' => false,
                'tournaments' => [],
            ]);

            return;
        }

        TournamentAuth::requireSuperAdmin();

        $filterTournamentId = (int) ($_GET['tournament_id'] ?? 0);
        $allTournaments = Tournament::all();

        if ($filterTournamentId > 0) {
            $tournament = Tournament::findById($filterTournamentId);
            $users = TournamentMember::usersForTournament($filterTournamentId);

            foreach ($users as &$user) {
                $user['tournament_label'] = $tournament
                    ? $tournament['name'] . ' (' . (int) $tournament['year'] . ')'
                    : '—';
            }
            unset($user);

            $this->view('admin/users', [
                'users' => $users,
                'tournament' => $tournament,
                'isSuperAdmin' => true,
                'isGlobalList' => false,
                'tournaments' => $allTournaments,
                'filterTournamentId' => $filterTournamentId,
            ]);

            return;
        }

        $this->view('admin/users', [
            'users' => User::allWithTournaments(),
            'tournament' => null,
            'isSuperAdmin' => true,
            'isGlobalList' => true,
            'tournaments' => $allTournaments,
            'filterTournamentId' => 0,
        ]);
    }

    public function toggleUser(): void
    {
        Csrf::validateOrAbort();
        TournamentAuth::requireSuperAdmin();

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
        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);
        $admin = Auth::user();

        if (!$id || $id === (int) $admin['id']) {
            Flash::set('error', 'Invalid user.');
            $this->redirect('/admin/users');
        }

        if (Auth::isHost()) {
            $hostedId = TournamentAuth::adminTournamentId();
            $tournamentId = $hostedId ?? $tournamentId;
            if (!$tournamentId) {
                Flash::set('error', 'No tournament assigned.');
                $this->redirect('/admin/users');
            }
            TournamentAuth::requireCanManageTournament($tournamentId);
        }

        try {
            $result = InvitationService::resendTemporaryPassword(
                $id,
                (int) $admin['id'],
                Auth::isHost() ? $tournamentId : null
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

        $redirect = Auth::isHost()
            ? '/admin/users'
            : '/admin/users' . ($tournamentId ? '?tournament_id=' . $tournamentId : '');

        $this->redirect($redirect);
    }

    public function matches(): void
    {
        $tournamentId = TournamentAuth::adminTournamentId(
            (int) ($_GET['tournament_id'] ?? 0) ?: null
        );
        $tournament = $tournamentId ? Tournament::findById($tournamentId) : null;
        $matches = [];
        $teams = [];

        if ($tournament) {
            TournamentAuth::requireCanManageTournament($tournamentId);
            $matches = MatchModel::forTournament($tournamentId);
            $teams = Team::forTournament($tournamentId);
        }

        $this->view('admin/matches', [
            'tournament' => $tournament,
            'matches' => $matches,
            'teams' => $teams,
            'isSuperAdmin' => Auth::isSuperAdmin(),
        ]);
    }

    public function importMatches(): void
    {
        Csrf::validateOrAbort();

        $tournamentId = TournamentAuth::adminTournamentId(
            (int) ($_POST['tournament_id'] ?? 0) ?: null
        );

        if (!$tournamentId) {
            Flash::set('error', 'No tournament selected.');
            $this->redirect('/admin/matches');
        }

        TournamentAuth::requireCanManageTournament($tournamentId);

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

        $result = MatchImportService::import($tournamentId, $content);

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
        $tournamentId = TournamentAuth::adminTournamentId(
            (int) ($_GET['tournament_id'] ?? 0) ?: null
        );
        $tournament = $tournamentId ? Tournament::findById($tournamentId) : null;
        $matches = [];

        if ($tournament) {
            TournamentAuth::requireCanManageTournament($tournamentId);
            $matches = MatchModel::forTournament($tournamentId);
        }

        $this->view('admin/results', [
            'tournament' => $tournament,
            'matches' => $matches,
            'isSuperAdmin' => Auth::isSuperAdmin(),
        ]);
    }

    public function saveResult(): void
    {
        Csrf::validateOrAbort();

        $matchId = (int) ($_POST['match_id'] ?? 0);
        $home = (int) ($_POST['home_score'] ?? 0);
        $away = (int) ($_POST['away_score'] ?? 0);

        $tournamentId = TournamentAuth::requireMatchInManagedTournament($matchId);

        MatchModel::updateScore($matchId, $home, $away);
        $scored = ScoringService::scoreMatch($matchId);

        Flash::set(
            'success',
            "Result saved. {$scored} prediction(s) scored."
        );

        $this->redirect(self::adminResultsRedirect($tournamentId));
    }

    public function updateMatchDetails(): void
    {
        Csrf::validateOrAbort();

        $matchId = (int) ($_POST['match_id'] ?? 0);
        $tournamentId = TournamentAuth::requireMatchInManagedTournament($matchId);

        $kickoffInput = trim($_POST['kickoff_at'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $venueCountry = trim($_POST['venue_country'] ?? '');

        $kickoff = date_create($kickoffInput, timezone_open(app_timezone()));

        if ($kickoff === false) {
            Flash::set('error', 'Enter a valid kickoff date and time.');
            $this->redirect(self::adminMatchesRedirect($tournamentId));
        }

        MatchModel::updateDetails(
            $matchId,
            $kickoff->format('Y-m-d H:i:s'),
            $venue !== '' ? $venue : null,
            $venueCountry !== '' ? $venueCountry : null
        );

        Flash::set('success', 'Match date and venue updated.');
        $this->redirect(self::adminMatchesRedirect($tournamentId));
    }

    public function togglePredictions(): void
    {
        Csrf::validateOrAbort();

        $matchId = (int) ($_POST['match_id'] ?? 0);
        $allowed = (int) ($_POST['allow_predictions'] ?? 0) === 1;

        $tournamentId = TournamentAuth::requireMatchInManagedTournament($matchId);

        MatchModel::setAllowPredictions($matchId, $allowed);

        Flash::set('success', 'Prediction lock updated.');
        $this->redirect(self::adminMatchesRedirect($tournamentId));
    }

    private static function adminMatchesRedirect(int $tournamentId): string
    {
        return Auth::isSuperAdmin()
            ? '/admin/matches?tournament_id=' . $tournamentId
            : '/admin/matches';
    }

    private static function adminResultsRedirect(int $tournamentId): string
    {
        return Auth::isSuperAdmin()
            ? '/admin/results?tournament_id=' . $tournamentId
            : '/admin/results';
    }

    public function tournament(): void
    {
        if (Auth::isHost()) {
            $hosted = TournamentAuth::hostedTournament();

            if (!$hosted) {
                Flash::set('error', 'No tournament is assigned to your host account.');
                $this->view('admin/tournament', [
                    'tournaments' => [],
                    'active' => null,
                    'selected' => null,
                    'teams' => [],
                    'isSuperAdmin' => false,
                    'isHost' => true,
                ]);
                return;
            }

            $this->view('admin/tournament', [
                'tournaments' => [$hosted],
                'active' => $hosted,
                'selected' => $hosted,
                'teams' => Team::forTournament((int) $hosted['id']),
                'isSuperAdmin' => false,
                'isHost' => true,
            ]);
            return;
        }

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
            'isSuperAdmin' => true,
            'isHost' => false,
        ]);
    }

    public function storeTournament(): void
    {
        Csrf::validateOrAbort();
        TournamentAuth::requireSuperAdmin();

        $name = trim($_POST['name'] ?? '');
        $year = (int) ($_POST['year'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $hostName = trim($_POST['host_name'] ?? '');
        $hostEmail = trim($_POST['host_email'] ?? '');

        if (
            !Validator::required($name)
            || $year < 2000
            || !$start
            || !$end
            || !Validator::required($hostName)
            || !Validator::email($hostEmail)
        ) {
            Flash::set(
                'error',
                'Fill in tournament details and host name and email.'
            );
            $this->redirect('/admin/tournament');
        }

        $status = ($_POST['set_active'] ?? '') === '1' ? 'active' : 'upcoming';

        try {
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

            $hostResult = HostService::assignHostToTournament(
                $id,
                $hostName,
                $hostEmail,
                (int) Auth::user()['id']
            );

            $hostMsg = !empty($hostResult['existing_user'])
                ? ' Host account linked.'
                : ' Host welcome email sent to ' . $hostResult['email'] . '.';

            Flash::set('success', 'Tournament created.' . $hostMsg);
            $this->redirect('/admin/tournament?id=' . $id);
        } catch (\InvalidArgumentException $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/tournament');
        } catch (\Throwable $e) {
            Flash::set(
                'error',
                'Could not complete setup: ' . $e->getMessage()
            );
            $this->redirect('/admin/tournament');
        }
    }

    public function activateTournament(): void
    {
        Csrf::validateOrAbort();
        TournamentAuth::requireSuperAdmin();

        $id = (int) ($_POST['tournament_id'] ?? 0);

        if ($id) {
            TournamentSetupService::activate($id);
            Flash::set('success', 'Tournament activated.');
        }

        $this->redirect('/admin/tournament?id=' . $id);
    }

    public function storeTeam(): void
    {
        Csrf::validateOrAbort();

        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);
        TournamentAuth::requireCanManageTournament($tournamentId);

        $name = trim($_POST['name'] ?? '');
        $short = trim($_POST['short_name'] ?? '');
        $fifa = trim($_POST['fifa_code'] ?? '');

        if (!$tournamentId || !Validator::required($name) || !Validator::required($short) || !Validator::required($fifa)) {
            Flash::set('error', 'Team name, short name, and ISO code are required.');
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
        TournamentAuth::requireCanManageTournament($tournamentId);

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
}
