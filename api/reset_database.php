<?php
// api/reset_database.php
require_once '../includes/db_connect.php';

// SECURITY: Hardcoded secret to prevent accidental wipes
// Usage: api/reset_database.php?secret=KKYF_CLEAN_START_2026
$SECRET = 'KKYF_CLEAN_START_2026';

if (($_GET['secret'] ?? '') !== $SECRET) {
    die("⛔ Access Denied: Invalid Secret Key.");
}

header('Content-Type: text/plain');
echo "⚠️  STARTING DATABASE RESET ⚠️\n\n";

try {
    // Disable Foreign Key Checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. Truncate Data Tables
    $tablesToWipe = [
        'Members',
        'Attendance_Log',
        'Activity_Log',
        'Password_Resets',
        'Audit_Log' // If it exists
    ];

    foreach ($tablesToWipe as $table) {
        // Check if table exists first to avoid errors
        try {
            $pdo->exec("TRUNCATE TABLE $table");
            echo "✅ Truncated: $table\n";
        } catch (PDOException $e) {
            echo "⚠️  Skipped (Not Found): $table\n";
        }
    }

    // 2. Clean Admin Users (KEEP Super Admin)
    // Delete everyone who is NOT a Super Admin
    $stmt = $pdo->prepare("DELETE FROM Admin_User WHERE Role != 'Super Admin'");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "✅ Deleted $deleted Non-Super Admin users.\n";

    // 3. Reset First Timer Flags (Logic check)
    // Since Members table is truncated, no need to reset flags.

    // Re-enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n🎉 DATABASE CLEANUP COMPLETE.\n";
    echo "Only Super Admin accounts remain.\n";

} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage();
}
?>