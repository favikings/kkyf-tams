<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/mailer.php';

const PASSWORD_RESET_EMAIL_LIMIT = 3;
const PASSWORD_RESET_IP_LIMIT = 10;

function normalizePasswordResetEmail(string $email): string
{
    return strtolower(trim($email));
}

function passwordResetClientIp(): string
{
    $address = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return filter_var($address, FILTER_VALIDATE_IP) !== false ? $address : '0.0.0.0';
}

/**
 * Records the request, applies both rolling-hour limits, and returns mail data
 * only when an approved active user is eligible for a new reset link.
 *
 * @return array{id:int,name:string,email:string,token:string,reset_url:string}|null
 */
function issuePasswordReset(string $submittedEmail, string $requestIp): ?array
{
    $email = normalizePasswordResetEmail($submittedEmail);
    $emailHash = hash('sha256', $email);
    $requestIp = filter_var($requestIp, FILTER_VALIDATE_IP) !== false
        ? $requestIp
        : '0.0.0.0';
    $baseUrl = rtrim((string) APP_URL, '/');
    $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
    $debugEnabled = in_array(
        strtolower((string) env('APP_DEBUG', '')),
        ['1', 'true', 'yes', 'on'],
        true
    );
    if ($baseUrl === '' || !in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('APP_URL is not a valid HTTP URL.');
    }
    if (!$debugEnabled && $scheme !== 'https') {
        throw new RuntimeException('APP_URL must use HTTPS outside debug mode.');
    }

    $pdo = db();

    $pdo->beginTransaction();
    try {
        $attempt = $pdo->prepare(
            'INSERT INTO password_reset_attempts (email_hash, request_ip) VALUES (?, ?)'
        );
        $attempt->execute([$emailHash, $requestIp]);

        $emailCount = $pdo->prepare(
            'SELECT COUNT(*)
             FROM password_reset_attempts
             WHERE email_hash = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $emailCount->execute([$emailHash]);

        $ipCount = $pdo->prepare(
            'SELECT COUNT(*)
             FROM password_reset_attempts
             WHERE request_ip = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $ipCount->execute([$requestIp]);

        $userQuery = $pdo->prepare(
            "SELECT id, name, email
             FROM users
             WHERE email = ?
               AND status = 'approved'
               AND is_active = 1
             LIMIT 1
             FOR UPDATE"
        );
        $userQuery->execute([$email]);
        $user = $userQuery->fetch();

        $allowed = (int) $emailCount->fetchColumn() <= PASSWORD_RESET_EMAIL_LIMIT
            && (int) $ipCount->fetchColumn() <= PASSWORD_RESET_IP_LIMIT;

        if (!$allowed || $user === false) {
            $pdo->commit();

            return null;
        }

        $consumeOld = $pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL'
        );
        $consumeOld->execute([(int) $user['id']]);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $insertToken = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, request_ip, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $insertToken->execute([(int) $user['id'], $tokenHash, $requestIp]);
        $pdo->commit();

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'token' => $token,
            'reset_url' => $baseUrl . '/reset-password.php?token=' . rawurlencode($token),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function invalidatePasswordReset(string $token): void
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return;
    }

    $statement = db()->prepare(
        'UPDATE password_resets SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL'
    );
    $statement->execute([hash('sha256', $token)]);
}

/**
 * @return array{reset_id:int,user_id:int,name:string,email:string}|null
 */
function findValidPasswordReset(string $token): ?array
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return null;
    }

    $statement = db()->prepare(
        "SELECT pr.id AS reset_id, u.id AS user_id, u.name, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ?
           AND pr.used_at IS NULL
           AND pr.expires_at > NOW()
           AND u.status = 'approved'
           AND u.is_active = 1
         LIMIT 1"
    );
    $statement->execute([hash('sha256', $token)]);
    $row = $statement->fetch();

    if ($row === false) {
        return null;
    }

    return [
        'reset_id' => (int) $row['reset_id'],
        'user_id' => (int) $row['user_id'],
        'name' => (string) $row['name'],
        'email' => (string) $row['email'],
    ];
}

/**
 * Atomically consumes a valid token, changes the password, revokes all other
 * reset links, and increments auth_version to invalidate existing sessions.
 *
 * @return array{id:int,name:string,email:string}|null
 */
function consumePasswordReset(string $token, string $newPassword): ?array
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return null;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $lookup = $pdo->prepare(
            "SELECT pr.id AS reset_id, u.id AS user_id, u.name, u.email
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ?
               AND pr.used_at IS NULL
               AND pr.expires_at > NOW()
               AND u.status = 'approved'
               AND u.is_active = 1
             LIMIT 1
             FOR UPDATE"
        );
        $lookup->execute([hash('sha256', $token)]);
        $row = $lookup->fetch();

        if ($row === false) {
            $pdo->rollBack();

            return null;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            throw new RuntimeException('Could not hash the new password.');
        }

        $updateUser = $pdo->prepare(
            'UPDATE users
             SET password_hash = ?, auth_version = auth_version + 1
             WHERE id = ?'
        );
        $updateUser->execute([$passwordHash, (int) $row['user_id']]);

        $consumeTokens = $pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL'
        );
        $consumeTokens->execute([(int) $row['user_id']]);
        $pdo->commit();

        return [
            'id' => (int) $row['user_id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function sendPasswordResetEmail(array $reset): void
{
    $safeName = htmlspecialchars((string) $reset['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars((string) $reset['reset_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = 'Reset your ' . APP_NAME . ' password';
    $html = '<p>Hello ' . $safeName . ',</p>'
        . '<p>Use the link below to reset your password. It expires in 30 minutes and can only be used once.</p>'
        . '<p><a href="' . $safeUrl . '">Reset password</a></p>'
        . '<p>If you did not request this, you can ignore this email.</p>';
    $text = "Hello {$reset['name']},\n\n"
        . "Reset your password using this link (valid for 30 minutes):\n{$reset['reset_url']}\n\n"
        . "If you did not request this, you can ignore this email.";

    sendMail((string) $reset['email'], (string) $reset['name'], $subject, $html, $text);
}

function sendPasswordChangedEmail(array $user): void
{
    $safeName = htmlspecialchars((string) $user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = APP_NAME . ' password changed';
    $html = '<p>Hello ' . $safeName . ',</p>'
        . '<p>Your password was changed successfully.</p>'
        . '<p>If you did not make this change, contact your Super Admin immediately.</p>';
    $text = "Hello {$user['name']},\n\nYour password was changed successfully.\n\n"
        . 'If you did not make this change, contact your Super Admin immediately.';

    sendMail((string) $user['email'], (string) $user['name'], $subject, $html, $text);
}
