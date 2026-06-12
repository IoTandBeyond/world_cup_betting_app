<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BonusController;
use App\Controllers\PasswordController;
use App\Controllers\PolicyController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\GroupsController;
use App\Controllers\LeaderboardController;
use App\Controllers\PredictionController;
use App\Controllers\TournamentController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Services\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/invite/{token}', [AuthController::class, 'invite']);
$router->get('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/policy/accept', [PolicyController::class, 'acceptForm'], [AuthMiddleware::class]);
$router->post('/policy/accept', [PolicyController::class, 'accept'], [AuthMiddleware::class]);
$router->get('/password/change', [PasswordController::class, 'changeForm'], [AuthMiddleware::class]);
$router->post('/password/change', [PasswordController::class, 'change'], [AuthMiddleware::class]);

$router->get('/register/{token}', [RegisterController::class, 'form']);
$router->post('/register/{token}', [RegisterController::class, 'register']);

$router->post('/tournament/switch', [TournamentController::class, 'switch'], [AuthMiddleware::class]);

$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/bonus', [BonusController::class, 'index'], [AuthMiddleware::class]);
$router->post('/bonus', [BonusController::class, 'save'], [AuthMiddleware::class]);
$router->post('/predictions', [PredictionController::class, 'save'], [AuthMiddleware::class]);
$router->get('/leaderboard', [LeaderboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/groups', [GroupsController::class, 'index'], [AuthMiddleware::class]);

$router->get('/admin', [AdminController::class, 'dashboard'], [AdminMiddleware::class]);
$router->get('/admin/invitations', [AdminController::class, 'invitations'], [AdminMiddleware::class]);
$router->post('/admin/invitations', [AdminController::class, 'storeInvitation'], [AdminMiddleware::class]);
$router->get('/admin/users', [AdminController::class, 'users'], [AdminMiddleware::class]);
$router->post('/admin/users/toggle', [AdminController::class, 'toggleUser'], [AdminMiddleware::class]);
$router->post('/admin/users/resend-password', [AdminController::class, 'resendTemporaryPassword'], [AdminMiddleware::class]);
$router->get('/admin/tournament', [AdminController::class, 'tournament'], [AdminMiddleware::class]);
$router->post('/admin/tournament', [AdminController::class, 'storeTournament'], [AdminMiddleware::class]);
$router->post('/admin/tournament/activate', [AdminController::class, 'activateTournament'], [AdminMiddleware::class]);
$router->post('/admin/tournament/teams', [AdminController::class, 'storeTeam'], [AdminMiddleware::class]);
$router->post('/admin/tournament/import', [AdminController::class, 'importTeams'], [AdminMiddleware::class]);
$router->get('/admin/matches', [AdminController::class, 'matches'], [AdminMiddleware::class]);
$router->post('/admin/matches/import', [AdminController::class, 'importMatches'], [AdminMiddleware::class]);
$router->post('/admin/matches/toggle-predictions', [AdminController::class, 'togglePredictions'], [AdminMiddleware::class]);
$router->get('/admin/results', [AdminController::class, 'results'], [AdminMiddleware::class]);
$router->post('/admin/results', [AdminController::class, 'saveResult'], [AdminMiddleware::class]);

return $router;
