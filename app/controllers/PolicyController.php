<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\OnboardingService;
use App\Services\PolicyService;

class PolicyController extends Controller
{
    public function acceptForm(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        if (User::hasAcceptedPolicy($user)) {
            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        $this->view('auth/accept_policy', [
            'user' => $user,
            'policyVersion' => PolicyService::currentVersion(),
            'error' => Flash::get('error'),
        ]);
    }

    public function accept(): void
    {
        Csrf::validateOrAbort();

        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $user = Auth::user();

        if (empty($_POST['accept_policy'])) {
            Flash::set(
                'error',
                'You must accept the Rules & Policy to use the application.'
            );
            $this->redirect('/policy/accept');
        }

        User::recordPolicyAcceptance(
            (int) $user['id'],
            PolicyService::currentVersion()
        );

        Flash::set('success', 'Thank you. Please continue with your account setup.');

        if (Auth::mustChangePassword()) {
            $this->redirect('/password/change');
        }

        $this->redirect(OnboardingService::redirectAfterLogin());
    }
}
