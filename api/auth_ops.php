<?php
// api/auth_ops.php
require_once __DIR__ . '/../includes/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Only require if PHPMailer is downloaded into includes folder
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';

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

        // 4. Send Email
        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . BASE_PATH . "/reset_password.php?token=" . $token;

        if (SMTP_PASS === 'YOUR_EMAIL_PASSWORD_HERE') {
            // Fallback for Development (Or if user forgets to set password)
            sendJson(true, ['message' => 'Reset link generated (Simulation Mode)', 'reset_link' => $resetLink]);
        }

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'KKYF Tent Manager - Password Reset Request';
            
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #00BD06;'>Password Reset Request</h2>
                    <p>Hello,</p>
                    <p>You have requested to reset your password for the KKYF Tent Manager system.</p>
                    <p>Please click the button below to set a new password:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' style='background-color: #00BD06; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset Password</a>
                    </div>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request this, please ignore this email.</p>
                </div>
            ";
            $mail->Body = $body;

            $mail->send();
            sendJson(true, ['message' => 'Reset link sent to your email. Check your inbox.']);
        } catch (PHPMailerException $e) {
            error_log("Email sending failed. Mailer Error: {$mail->ErrorInfo}");
            throw new Exception("Could not send email. Please contact support.");
        }

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