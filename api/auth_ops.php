<?php
// api/auth_ops.php
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

// Helper to send JSON response
function sendJson($success, $data = [], $error = null)
{
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson(false, [], "Invalid request method");
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        // 1. Check if email exists
        $stmt = $pdo->prepare("SELECT ID FROM Admin_User WHERE Email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            // Security: Don't reveal if email exists, just pretend success
            // But for this internal tool, maybe we want to know? 
            // Let's return success but no link.
            // Actually, for user experience in this closed system, let's say "Email not found" if that's preferred, 
            // but standard practice is "If that email exists...".
            // Let's stick to standard practice: Pretend success.
            // BUT, since we need to SIMULATE the link, we must know if it succeeded to return the link.
            // So we WILL throw error if not found, for now.
            throw new Exception("Email not found in our records.");
        }

        // 2. Generate Token
        $token = bin2hex(random_bytes(32)); // 64 chars
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Store Token
        $stmtInsert = $pdo->prepare("INSERT INTO Password_Resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmtInsert->execute([$email, $token, $expiresAt]);

        // 4. Simulate Email (Return Link)
        // In production, send email here.
        $resetLink = BASE_PATH . "/reset_password.php?token=" . $token;

        sendJson(true, ['message' => 'Reset link generated', 'reset_link' => $resetLink]);

    } elseif ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['password'] ?? '';

        if (empty($token) || strlen($newPassword) < 6) {
            throw new Exception("Invalid token or password too short.");
        }

        // 1. Validate Token
        $stmt = $pdo->prepare("SELECT email FROM Password_Resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $email = $stmt->fetchColumn();

        if (!$email) {
            throw new Exception("Invalid or expired reset token.");
        }

        // 2. Update Password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE Admin_User SET Password_Hash = ? WHERE Email = ?");
        $stmtUpdate->execute([$hash, $email]);

        // 3. Consume Token (Delete all for this email to be safe, or just this one)
        // Better to delete just this one or all to invalidate old ones.
        $stmtDelete = $pdo->prepare("DELETE FROM Password_Resets WHERE email = ?");
        $stmtDelete->execute([$email]);

        sendJson(true, ['message' => 'Password reset successfully. You can now login.']);

    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    sendJson(false, [], $e->getMessage());
}
?>