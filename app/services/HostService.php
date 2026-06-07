<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tournament;
use App\Models\User;

class HostService
{
    public static function assignHostToTournament(
        int $tournamentId,
        string $name,
        string $email,
        int $invitedByAdminId
    ): array {
        $email = strtolower(trim($email));
        $name = trim($name);

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Host name and a valid email are required.');
        }

        $tournament = Tournament::findById($tournamentId);

        if (!$tournament) {
            throw new \InvalidArgumentException('Tournament not found.');
        }

        if (!empty($tournament['host_user_id'])) {
            throw new \InvalidArgumentException('This tournament already has a host assigned.');
        }

        $existing = User::findByEmail($email);

        if ($existing) {
            if ($existing['role'] === 'admin') {
                throw new \InvalidArgumentException(
                    'That email belongs to a super-admin account.'
                );
            }

            if ($existing['role'] === 'host') {
                $other = Tournament::findByHostUserId((int) $existing['id']);

                if ($other && (int) $other['id'] !== $tournamentId) {
                    throw new \InvalidArgumentException(
                        'That host already manages another tournament.'
                    );
                }

                Tournament::setHost($tournamentId, (int) $existing['id']);

                return [
                    'user_id' => (int) $existing['id'],
                    'email' => $email,
                    'existing_user' => true,
                ];
            }

            throw new \InvalidArgumentException(
                'That email is already registered as a player. Use a different email for the host.'
            );
        }

        $tempPassword = InvitationService::generateTempPassword();
        $loginUrl = absolute_url('/login');
        $appName = $_ENV['APP_NAME'] ?? 'World Cup Pool';

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $userId = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $tempPassword,
                'role' => 'host',
                'must_change_password' => true,
            ]);

            Tournament::setHost($tournamentId, $userId);

            $html = self::buildHostEmailHtml(
                $name,
                $email,
                $tempPassword,
                $loginUrl,
                $appName,
                $tournament['name']
            );

            $text = self::buildHostEmailText(
                $name,
                $email,
                $tempPassword,
                $loginUrl,
                $appName,
                $tournament['name']
            );

            MailService::send(
                $email,
                "You are the host for {$tournament['name']}",
                $html,
                $text
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'user_id' => $userId,
            'email' => $email,
            'existing_user' => false,
        ];
    }

    private static function buildHostEmailHtml(
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
    <p>You have been assigned as <strong>tournament host</strong> for <strong>{$safeTournament}</strong> on {$safeApp}.</p>
    <p>As host you can add teams, import matches, send player invitations, and enter results for your tournament.</p>
    <table cellpadding="8" style="background:#f4f6f8;border-radius:8px;">
        <tr><td><strong>Email</strong></td><td>{$safeEmail}</td></tr>
        <tr><td><strong>Temporary password</strong></td><td><code style="font-size:16px;letter-spacing:1px;">{$safePass}</code></td></tr>
    </table>
    <p><a href="{$safeUrl}" style="display:inline-block;padding:10px 18px;background:#0d6b3a;color:#fff;text-decoration:none;border-radius:6px;">Sign in to manage your tournament</a></p>
    <p><strong>Important:</strong> Accept the rules, set a new password, then open the admin area to set up teams and matches.</p>
</body>
</html>
HTML;
    }

    private static function buildHostEmailText(
        string $name,
        string $email,
        string $tempPassword,
        string $loginUrl,
        string $appName,
        string $tournamentName
    ): string {
        return <<<TEXT
Hi {$name},

You are the tournament host for {$tournamentName} on {$appName}.

Email: {$email}
Temporary password: {$tempPassword}

Sign in: {$loginUrl}

You can add teams, matches, invitations, and results for your tournament after you set your password.

TEXT;
    }
}
