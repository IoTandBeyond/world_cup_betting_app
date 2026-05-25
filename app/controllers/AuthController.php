<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\OnboardingService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        $prefillEmail = strtolower(trim($_GET['email'] ?? ''));

        if ($prefillEmail !== '' && !Validator::email($prefillEmail)) {
            $prefillEmail = '';
        }

        $this->view('auth/login', [
            'error' => Flash::get('error'),
            'prefillEmail' => $prefillEmail,
        ]);
    }

    public function invite(string $token): void
    {
        $invitation = Invitation::findByToken($token);

        if (!Invitation::allowsLoginAssist($invitation)) {
            Flash::set('error', 'This invitation link is invalid or has expired.');
            $this->redirect('/login');
        }

        $email = $invitation['email'];

        if (User::findByEmail($email)) {
            Flash::set(
                'success',
                'Your account is ready. Sign in with the temporary password from your email (format XXXX-XXXX-XXXX).'
            );
            $this->redirect('/login?email=' . rawurlencode($email));
        }

        $this->redirect('/register/' . $token);
    }

    public function login(): void
    {
        Csrf::validateOrAbort();

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if (!Validator::email($email) || !Validator::required($password)) {
            Flash::set('error', 'Please enter a valid email and password.');
            $this->redirect('/login');
        }

        if (Auth::attempt($email, $password)) {
            if (!\App\Models\User::hasAcceptedPolicy(Auth::user())) {
                Flash::set(
                    'success',
                    'Please review and accept the Rules & Policy to continue.'
                );
            } elseif (Auth::mustChangePassword()) {
                Flash::set('success', 'Please create your new password to continue.');
            }

            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        Flash::set('error', 'Invalid credentials or inactive account.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
