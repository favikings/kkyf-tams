<?php
// api/get_birthdays.php
require_once __DIR__ . '/../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Auth Check (Tent Admin Only)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Tent Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'] ?? '';
$tentId = $_SESSION['assigned_tent_id'] ?? null;

if ($role === 'Super Admin' && isset($_GET['tent_id'])) {
    $tentId = $_GET['tent_id'];
}
if (!$tentId) {
    echo json_encode(['success' => false, 'error' => 'No Tent Assigned']);
    exit;
}

// 2. Input Validation
$filter = isset($_GET['filter']) && $_GET['filter'] === 'month' ? 'month' : 'week';
$daysLimit = $filter === 'month' ? 30 : 7;

try {
    // 3. Date Logic (Handling Year Wraps & Leap Years)
    // We calculate "Next Birthday Date" and then diff it with "Today"
    // MySQL Logic: 
    // IF( DAYOFYEAR(DOB) < DAYOFYEAR(NOW()), Add 1 Year, Add 0 Year ) to DOB Year
    // Then DATEDIFF

    $sql = "
        SELECT 
            Member_ID, 
            Full_Name, 
            Birthdate,
            DATE_FORMAT(Birthdate, '%b %d') as formatted_date,
            DATEDIFF(
                DATE_ADD(Birthdate, INTERVAL YEAR(CURDATE()) - YEAR(Birthdate) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(Birthdate), 1, 0) YEAR),
                CURDATE()
            ) AS days_until
        FROM Members
        WHERE Current_Tent_ID = ?
        AND Birthdate IS NOT NULL
        HAVING days_until BETWEEN 0 AND ?
        ORDER BY days_until ASC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tentId, $daysLimit]);
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $birthdays]);

} catch (PDOException $e) {
    error_log("Birthday API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
