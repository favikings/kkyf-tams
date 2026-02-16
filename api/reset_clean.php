<?php
// api/reset_clean.php
require_once '../includes/db_connect.php';

header('Content-Type: text/plain');

try {
    echo "--- Database Schema Reset (Partial) ---\n";

    // 1. Disable FK Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Foreign Key Checks Disabled.\n";

    // 2. Drop Corrupted Tables
    $pdo->exec("DROP TABLE IF EXISTS Attendance_Log");
    echo "Dropped Table: Attendance_Log\n";

    $pdo->exec("DROP TABLE IF EXISTS Members");
    echo "Dropped Table: Members\n";

    // 3. Re-Create Members (Clean Schema)
    $sqlMembers = "
    CREATE TABLE IF NOT EXISTS Members (
        Member_ID INT AUTO_INCREMENT PRIMARY KEY,
        Member_UUID VARCHAR(36) NOT NULL UNIQUE, 
        Full_Name VARCHAR(100) NOT NULL,
        Status ENUM('Student', 'Worker', 'Alumni') NOT NULL DEFAULT 'Student',
        School VARCHAR(100) NULL,
        Birthdate DATE NULL,
        Phone VARCHAR(20) NULL,
        Address TEXT NULL,
        Current_Tent_ID INT NOT NULL,
        Join_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (Current_Tent_ID) REFERENCES Tents(Tent_ID)
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sqlMembers);
    echo "Re-Created Table: Members (Clean)\n";

    // 4. Re-Create Attendance_Log (Clean Schema)
    $sqlLog = "
    CREATE TABLE IF NOT EXISTS Attendance_Log (
        Log_ID INT AUTO_INCREMENT PRIMARY KEY,
        Member_UUID VARCHAR(36) NOT NULL,
        Session_ID INT NOT NULL,
        Tent_ID INT NOT NULL, 
        Attendance_Date DATE NOT NULL,
        Is_First_Timer BOOLEAN DEFAULT 0,
        Check_In_Time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (Member_UUID) REFERENCES Members(Member_UUID) ON DELETE CASCADE,
        FOREIGN KEY (Session_ID) REFERENCES Sessions(Session_ID),
        FOREIGN KEY (Tent_ID) REFERENCES Tents(Tent_ID),
        UNIQUE KEY unique_attendance (Member_UUID, Attendance_Date)
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($sqlLog);
    echo "Re-Created Table: Attendance_Log (Clean)\n";

    // 5. Re-Enable FK Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Foreign Key Checks Enabled.\n";

    echo "\nSUCCESS: Database Schema has been reset to match the Blueprint.";

} catch (PDOException $e) {
    echo "Reset Failed: " . $e->getMessage();
}
?>