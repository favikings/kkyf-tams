<?php
// api/get_first_timers.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

checkAuth('Tent Admin');

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';
$tentId = $_SESSION['assigned_tent_id'] ?? null;

if ($role === 'Super Admin' && isset($_GET['tent_id'])) {
    $tentId = $_GET['tent_id'];
}
$filter = $_GET['filter'] ?? 'recent'; // 'recent' (30 days) or 'all'

try {
    $sql = "
        SELECT 
            m.Full_Name,
            m.Phone,
            m.Status,
            al.Attendance_Date,
            al.Check_In_Time
        FROM Attendance_Log al
        JOIN Members m ON al.Member_UUID = m.Member_UUID
        WHERE al.Tent_ID = ? 
        AND al.Is_First_Timer = 1
    ";

    if ($filter === 'recent') {
        $sql .= " AND al.Attendance_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql .= " ORDER BY al.Attendance_Date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tentId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    error_log("First Timer API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>