<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;

class InvitationService
{
    public static function sendInvitation(
        string $email,
        int $invitedBy,
        int $tournamentId,
        ?string $name = null
    ): array {
        $email = strtolower(trim($email));
        $tournament = \App\Models\Tournament::findById($tournamentId);

        if (!$tournament) {
            throw new \InvalidArgumentException('Tournament not found.');
        }

        $existing = User::findByEmail($email);

        if ($existing) {
            if ($existing['role'] !== 'user') {
                throw new \InvalidArgumentException(
                    'That email belongs to an admin or host account.'
                );
            }

            if (\App\Models\TournamentMember::isMember((int) $existing['id'], $tournamentId)) {
                throw new \InvalidArgumentException(
                    'That player is already in this tournament.'
                );
            }

            return self::addExistingUserToTournament(
                $existing,
                $tournamentId,
                $invitedBy,
                $tournament['name']
            );
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $tempPassword = self::generateTempPassword();
        $displayName = self::resolveName($email, $name);
        $appName = $_ENV['APP_NAME'] ?? 'World Cup Pool';
        $loginUrl = self::inviteLink($token);

        $db = Database::connection();
        $db->beginTransaction();

        $userId = null;

        try {
            $invitationId = Invitation::create(
                $email,
                $token,
                $invitedBy,
                $expiresAt,
                $tournamentId
            );

            $userId = User::create([
                'name' => $displayName,
                'email' => $email,
                'password' => $tempPassword,
                'must_change_password' => true,
            ]);

            \App\Models\TournamentMember::add($tournamentId, $userId);

            $html = self::buildInvitationEmailHtml(
                $displayName,
                $email,
                $tempPassword,
                $loginUrl,
                $appName,
                $tournament['name']
            );

            $text = self::buildInvitationEmailText(
                $displayName,
                $email,
                $tempPassword,
                $loginUrl,
                $appName,
                $tournament['name']
            );

            MailService::send(
                $email,
                "Your {$tournament['name']} invitation — {$appName}",
                $html,
                $text
            );

            Invitation::markUsed($invitationId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'email' => $email,
            'user_id' => $userId,
            'name' => $displayName,
            'login_url' => $loginUrl,
            'email_sent' => true,
        ];
    }

    /** @param array<string, mixed> $user */
    private static function addExistingUserToTournament(
        array $user,
        int $tournamentId,
        int $invitedBy,
        string $tournamentName
    ): array {
        $appName = $_ENV['APP_NAME'] ?? 'World Cup Pool';
        $loginUrl = absolute_url('/login');

        \App\Models\TournamentMember::add($tournamentId, (int) $user['id']);

        $html = self::buildAddedToTournamentEmailHtml(
            $user['name'],
            $tournamentName,
            $loginUrl,
            $appName
        );

        $text = self::buildAddedToTournamentEmailText(
            $user['name'],
            $tournamentName,
            $loginUrl,
            $appName
        );

        MailService::send(
            $user['email'],
            "You were added to {$tournamentName}",
            $html,
            $text
        );

        return [
            'email' => $user['email'],
            'user_id' => (int) $user['id'],
            'name' => $user['name'],
            'login_url' => $loginUrl,
            'email_sent' => true,
            'existing_user' => true,
        ];
    }

    public static function resendTemporaryPassword(
        int $userId,
        int $invitedBy,
        ?int $tournamentId = null
    ): array {
        $user = User::findById($userId);

        if (!$user) {
            throw new \InvalidArgumentException('User not found.');
        }

        if ($user['role'] === 'admin' || $user['role'] === 'host') {
            throw new \InvalidArgumentException(
                'Cannot reset the password for an admin or host account this way.'
            );
        }

        if ($tournamentId !== null
            && !\App\Models\TournamentMember::isMember($userId, $tournamentId)
        ) {
            throw new \InvalidArgumentException(
                'That user is not in your tournament.'
            );
        }

        if (!(int) $user['is_active']) {
            throw new \InvalidArgumentException(
                'User is inactive. Activate the account before resending a password.'
            );
        }

        $email = strtolower(trim($user['email']));
        $displayName = $user['name'];
        $tempPassword = self::generateTempPassword();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $loginUrl = self::inviteLink($token);
        $appName = $_ENV['APP_NAME'] ?? 'World Cup Pool';

        $db = Database::connection();
        $db->beginTransaction();

        try {
            User::setTemporaryPassword($userId, $tempPassword);

            $invitationId = Invitation::create(
                $email,
                $token,
                $invitedBy,
                $expiresAt,
                $tournamentId
            );

            $html = self::buildResendEmailHtml(
                $displayName,
                $email,
                $tempPassword,
                $loginUrl,
                $appName
            );

            $text = self::buildResendEmailText(
                $displayName,
                $email,
                $tempPassword,
                $loginUrl,
                $appName
            );

            MailService::send(
                $email,
                "Your {$appName} temporary password",
                $html,
                $text
            );

            Invitation::markUsed($invitationId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'email' => $email,
            'user_id' => $userId,
            'login_url' => $loginUrl,
        ];
    }

    public static function generateTempPassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $parts = [];

        for ($i = 0; $i < 3; $i++) {
            $segment = '';

            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $parts[] = $segment;
        }

        return implode('-', $parts);
    }

    private static function resolveName(string $email, ?string $name): string
    {
        $name = trim($name ?? '');

        if ($name !== '') {
            return $name;
        }

        $local = strstr($email, '@', true) ?: $email;

        return ucwords(str_replace(['.', '_', '-'], ' ', $local));
    }

    private static function buildInvitationEmailHtml(
        string $name,
        string $email,
        string $tempPassword,
        string $loginUrl,
        string $appName,
        string $tournamentName
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePass = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $safeApp = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeTournament = htmlspecialchars($tournamentName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#222;">
    <p>Hi {$safeName},</p>
    <p>You have been invited to join <strong>{$safeTournament}</strong> on <strong>{$safeApp}</strong>.</p>
    <p>Sign in with these credentials:</p>
    <table cellpadding="8" style="background:#f4f6f8;border-radius:8px;">
        <tr><td><strong>Email</strong></td><td>{$safeEmail}</td></tr>
        <tr><td><strong>Temporary password</strong></td><td><code style="font-size:16px;letter-spacing:1px;">{$safePass}</code></td></tr>
    </table>
    <p><a href="{$safeUrl}" style="display:inline-block;padding:10px 18px;background:#0d6b3a;color:#fff;text-decoration:none;border-radius:6px;">Open sign-in page</a></p>
    <p><strong>Important:</strong> Use the <strong>temporary password</strong> above (not the link token). You will accept the rules and set a new password on first login.</p>
    <p style="color:#666;font-size:12px;">If you did not expect this email, you can ignore it.</p>
</body>
</html>
HTML;
    }

    private static function buildInvitationEmailText(
        string $name,
        string $email,
        string $tempPassword,
        string $loginUrl,
        string $appName,
        string $tournamentName
    ): string {
        return <<<TEXT
Hi {$name},

You have been invited to join {$tournamentName} on {$appName}.

Email: {$email}
Temporary password: {$tempPassword}

Sign-in page: {$loginUrl}

Use the temporary password above to log in (not the long token in the URL).
You must accept the rules and create a new password on your first login.

TEXT;
    }

    private static function buildAddedToTournamentEmailHtml(
        string $name,
        string $tournamentName,
        string $loginUrl,
        string $appName
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeTournament = htmlspecialchars($tournamentName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $safeApp = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#222;">
    <p>Hi {$safeName},</p>
    <p>You have been added to <strong>{$safeTournament}</strong> on {$safeApp}.</p>
    <p>Sign in with your existing password to make predictions for this tournament.</p>
    <p><a href="{$safeUrl}" style="display:inline-block;padding:10px 18px;background:#0d6b3a;color:#fff;text-decoration:none;border-radius:6px;">Log in</a></p>
</body>
</html>
HTML;
    }

    private static function buildAddedToTournamentEmailText(
        string $name,
        string $tournamentName,
        string $loginUrl,
        string $appName
    ): string {
        return <<<TEXT
Hi {$name},

You have been added to {$tournamentName} on {$appName}.

Log in with your existing password: {$loginUrl}

TEXT;
    }

    public static function inviteLink(string $token): string
    {
        return absolute_url('/invite/' . $token);
    }

    private static function buildResendEmailHtml(
        string $name,
        string $email,
        string $tempPassword,
        string $loginUrl,
        string $appName
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePass = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $safeApp = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#222;">
    <p>Hi {$safeName},</p>
    <p>An administrator has issued a new <strong>temporary password</strong> for your <strong>{$safeApp}</strong> account.</p>
    <p>Sign in with:</p>
    <table cellpadding="8" style="background:#f4f6f8;border-radius:8px;">
        <tr><td><strong>Email</strong></td><td>{$safeEmail}</td></tr>
        <tr><td><strong>New temporary password</strong></td><td><code style="font-size:16px;letter-spacing:1px;">{$safePass}</code></td></tr>
    </table>
    <p><a href="{$safeUrl}" style="display:inline-block;padding:10px 18px;background:#0d6b3a;color:#fff;text-decoration:none;border-radius:6px;">Open sign-in page</a></p>
    <p><strong>Important:</strong> Your previous password no longer works. Use the new temporary password above. You will be asked to set a new personal password after you sign in if required.</p>
    <p style="color:#666;font-size:12px;">If you did not request this, contact the pool organizer.</p>
</body>
</html>
HTML;
    }

    private static function buildResendEmailText(
        string $name,
        string $email,
        string $tempPassword,
        string $loginUrl,
        string $appName
    ): string {
        return <<<TEXT
Hi {$name},

A new temporary password was issued for your {$appName} account.

Email: {$email}
New temporary password: {$tempPassword}

Sign-in page: {$loginUrl}

Your previous password no longer works. Use the new temporary password above to log in.

TEXT;
    }

    public static function validateForRegistration(
        string $token,
        string $email
    ): ?string {
        $invitation = Invitation::findByToken($token);

        if (!Invitation::isValid($invitation)) {
            return 'This invitation is invalid or has expired.';
        }

        if (strtolower(trim($email)) !== strtolower($invitation['email'])) {
            return 'Email must match the invited address.';
        }

        if (User::findByEmail($email)) {
            return 'Your account was already created. Use the temporary password from your email to log in.';
        }

        return null;
    }

    public static function redeem(string $token): void
    {
        $invitation = Invitation::findByToken($token);

        if ($invitation) {
            Invitation::markUsed((int) $invitation['id']);
        }
    }
}
