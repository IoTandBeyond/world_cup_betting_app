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
        ?string $name = null
    ): array {
        $email = strtolower(trim($email));

        if (User::findByEmail($email)) {
            throw new \InvalidArgumentException(
                'An account with this email already exists.'
            );
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $tempPassword = self::generateTempPassword();
        $displayName = self::resolveName($email, $name);

        Invitation::create($email, $token, $invitedBy, $expiresAt);

        $userId = User::create([
            'name' => $displayName,
            'email' => $email,
            'password' => $tempPassword,
            'must_change_password' => true,
        ]);

        Invitation::markUsed(
            (int) Invitation::findByToken($token)['id']
        );

        $loginUrl = absolute_url('/login');
        $appName = $_ENV['APP_NAME'] ?? 'World Cup Pool';

        $html = self::buildInvitationEmailHtml(
            $displayName,
            $email,
            $tempPassword,
            $loginUrl,
            $appName
        );

        $text = self::buildInvitationEmailText(
            $displayName,
            $email,
            $tempPassword,
            $loginUrl,
            $appName
        );

        MailService::send(
            $email,
            "Your {$appName} account is ready",
            $html,
            $text
        );

        return [
            'email' => $email,
            'user_id' => $userId,
            'name' => $displayName,
            'login_url' => $loginUrl,
            'email_sent' => true,
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
    <p>You have been invited to join <strong>{$safeApp}</strong>.</p>
    <p>Sign in with these credentials:</p>
    <table cellpadding="8" style="background:#f4f6f8;border-radius:8px;">
        <tr><td><strong>Email</strong></td><td>{$safeEmail}</td></tr>
        <tr><td><strong>Temporary password</strong></td><td><code style="font-size:16px;">{$safePass}</code></td></tr>
    </table>
    <p><a href="{$safeUrl}" style="display:inline-block;padding:10px 18px;background:#0d6b3a;color:#fff;text-decoration:none;border-radius:6px;">Log in now</a></p>
    <p><strong>Important:</strong> You will be asked to create a new password on your first login. This temporary password will stop working after that.</p>
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
        string $appName
    ): string {
        return <<<TEXT
Hi {$name},

You have been invited to join {$appName}.

Email: {$email}
Temporary password: {$tempPassword}

Log in: {$loginUrl}

You must create a new password on your first login.

TEXT;
    }

    public static function inviteLink(string $token): string
    {
        return absolute_url('/register/' . $token);
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
            return 'Your account was already created. Check your email and log in.';
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
