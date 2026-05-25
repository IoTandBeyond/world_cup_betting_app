<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\OnboardingService;

class PasswordController extends Controller
{
    public function changeForm(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        if (!User::hasAcceptedPolicy($user)) {
            $this->redirect('/policy/accept');
        }

        if (!(int) ($user['must_change_password'] ?? 0)) {
            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        $this->view('auth/change_password', [
            'user' => $user,
            'error' => Flash::get('error'),
        ]);
    }

    public function change(): void
    {
        Csrf::validateOrAbort();

        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';

        if (!Validator::min($password, 8) || $password !== $confirm) {
            Flash::set('error', 'Password must be at least 8 characters and match confirmation.');
            $this->redirect('/password/change');
        }

        User::updatePassword((int) $user['id'], $password);

        Flash::set('success', 'Your password was updated. Welcome!');
        $this->redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
    }
}
