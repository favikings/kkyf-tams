<?php
// api/get_attendance_history.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Tent Admin');

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';
$tentId = $_SESSION['assigned_tent_id'] ?? null;

if ($role === 'Super Admin' && isset($_GET['tent_id'])) {
    $tentId = $_GET['tent_id'];
}
$mode = $_GET['mode'] ?? 'summary'; // 'summary' or 'detail'
$date = $_GET['date'] ?? null;

try {
    if ($mode === 'detail' && $date) {
        // Detailed list for a specific date
        $sql = "
            SELECT 
                m.Full_Name,
                m.Status,
                m.Phone,
                al.Check_In_Time
            FROM Attendance_Log al
            JOIN Members m ON al.Member_UUID = m.Member_UUID
            WHERE al.Tent_ID = ? AND al.Attendance_Date = ?
            ORDER BY al.Check_In_Time DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tentId, $date]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Summary list of Sundays
        $sql = "
            SELECT 
                Attendance_Date,
                COUNT(*) as Total_Attendance,
                SUM(Is_First_Timer) as First_Timers
            FROM Attendance_Log
            WHERE Tent_ID = ?
            GROUP BY Attendance_Date
            ORDER BY Attendance_Date DESC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tentId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    error_log("History API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>