<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\InvitationService;

class RegisterController extends Controller
{
    public function form(string $token): void
    {
        $invitation = Invitation::findByToken($token);

        if (!$invitation || !Invitation::allowsLoginAssist($invitation)) {
            Flash::set('error', 'This invitation is invalid or has expired.');
            $this->redirect('/login');
        }

        if (User::findByEmail($invitation['email'])) {
            Flash::set(
                'success',
                'Your account is already set up. Log in with the temporary password from your email.'
            );
            $this->redirect('/login?email=' . rawurlencode($invitation['email']));
        }

        if (!Invitation::isValid($invitation)) {
            Flash::set('error', 'This invitation has already been used.');
            $this->redirect('/login');
        }

        $this->view('auth/register', [
            'token' => $token,
            'email' => $invitation['email'],
            'error' => Flash::get('error'),
        ]);
    }

    public function register(string $token): void
    {
        Csrf::validateOrAbort();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';

        if (
            !Validator::required($name)
            || !Validator::email($email)
            || !Validator::min($password, 8)
            || $password !== $confirm
        ) {
            Flash::set('error', 'Please complete all fields (password min 8 characters).');
            $this->redirect('/register/' . $token);
        }

        $inviteError = InvitationService::validateForRegistration($token, $email);

        if ($inviteError) {
            Flash::set('error', $inviteError);
            $this->redirect('/register/' . $token);
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        InvitationService::redeem($token);

        Flash::set('success', 'Account created. You can log in now.');
        $this->redirect('/login');
    }
}
