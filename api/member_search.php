<?php
// api/member_search.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Auth: Tent Admin
checkAuth('Tent Admin');

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';

// Determine Tent Context
$tentId = $_SESSION['assigned_tent_id'] ?? null;

if ($role === 'Super Admin' && isset($_GET['tent_id'])) {
    // Super Admin Impersonation
    $tentId = $_GET['tent_id'];
}

$query = $_GET['query'] ?? '';

$scope = $_GET['scope'] ?? 'search';

if ($scope !== 'tent_all' && strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    // Base SQL
    $sql = "
        SELECT 
            m.Member_ID, 
            m.Member_UUID,
            m.Full_Name, 
            m.Status, 
            m.Phone,
            m.Birthdate,
            m.School,
            m.Join_Date,
            (CASE WHEN al.Log_ID IS NOT NULL THEN 1 ELSE 0 END) as has_attended_today
        FROM Members m
        LEFT JOIN Attendance_Log al ON m.Member_UUID = al.Member_UUID 
            AND al.Attendance_Date = CURDATE() 
            AND al.Tent_ID = ?
        WHERE m.Current_Tent_ID = ? 
    ";

    $params = [$tentId, $tentId];

    if ($scope === 'tent_all') {
        // Master List Mode (Optional Search Filter)
        if (strlen($query) >= 1) {
            $sql .= " AND (m.Full_Name LIKE ? OR m.Phone LIKE ?) ";
            $searchTerm = "%$query%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        $sql .= " ORDER BY m.Full_Name ASC LIMIT 500";
    } else {
        // Standard Search Mode
        $sql .= " AND (m.Full_Name LIKE ? OR m.Phone LIKE ?) ";
        $searchTerm = "%$query%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $sql .= " ORDER BY m.Full_Name ASC LIMIT 20";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $members]);

} catch (PDOException $e) {
    error_log("Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>