<?php
// api/get_birthdays.php
require_once __DIR__ . '/../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Auth Check (Tent Admin OR Super Admin)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Tent Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'] ?? '';
$tentId = $_SESSION['assigned_tent_id'] ?? null;

// Allow Super Admin to view all tents or filter by tent
$viewAllTents = false;
if ($role === 'Super Admin') {
    if (isset($_GET['tent_id']) && $_GET['tent_id'] === 'all') {
        $viewAllTents = true;
        $tentId = null;
    } elseif (isset($_GET['tent_id'])) {
        $tentId = $_GET['tent_id'];
    }
}

// Get month filter (1-12)
$monthFilter = isset($_GET['month']) ? intval($_GET['month']) : 0;

// Get mode: 'upcoming' (default) or 'all' (list by month)
$mode = $_GET['mode'] ?? 'upcoming';

try {
    if ($mode === 'all' && $monthFilter > 0) {
        // MODE: All birthdays in a specific month (sorted by day)
        $sql = "
            SELECT 
                m.Member_ID, 
                m.Full_Name, 
                m.Birthdate,
                m.Phone,
                t.Tent_Name,
                DATE_FORMAT(m.Birthdate, '%b %d') as formatted_date,
                DAYOFMONTH(m.Birthdate) as day_of_month,
                MONTH(m.Birthdate) as month_num
            FROM Members m
            LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
            WHERE MONTH(m.Birthdate) = ?
            AND m.Birthdate IS NOT NULL
        ";
        
        $params = [$monthFilter];
        
        if (!$viewAllTents && $tentId) {
            $sql .= " AND m.Current_Tent_ID = ?";
            $params[] = $tentId;
        }
        
        $sql .= " ORDER BY day_of_month ASC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // MODE: Upcoming birthdays (next 30 days) - FIXED ALGORITHM
        $sql = "
            SELECT 
                m.Member_ID, 
                m.Full_Name, 
                m.Birthdate,
                m.Phone,
                t.Tent_Name,
                DATE_FORMAT(m.Birthdate, '%b %d') as formatted_date,
                DATEDIFF(
                    STR_TO_DATE(
                        CONCAT(
                            YEAR(CURDATE()), 
                            '-', 
                            LPAD(MONTH(m.Birthdate), 2, '0'), 
                            '-', 
                            LPAD(DAYOFMONTH(m.Birthdate), 2, '0')
                        ),
                        '%Y-%m-%d'
                    ),
                    CURDATE()
                ) AS days_until
            FROM Members m
            LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
            WHERE m.Birthdate IS NOT NULL
        ";
        
        $params = [];
        
        if (!$viewAllTents && $tentId) {
            $sql .= " AND m.Current_Tent_ID = ?";
            $params[] = $tentId;
        }
        
        // Handle year boundary - show birthdays from last year that haven't happened yet this year
        // AND birthdays from this year that have passed
        $sql .= " AND (
            -- Birthday hasn't occurred yet this year (positive days)
            DATEDIFF(
                STR_TO_DATE(
                    CONCAT(YEAR(CURDATE()), '-', LPAD(MONTH(m.Birthdate), 2, '0'), '-', LPAD(DAYOFMONTH(m.Birthdate), 2, '0')),
                    '%Y-%m-%d'
                ),
                CURDATE()
            ) BETWEEN 0 AND 30
            OR
            -- Birthday already passed this year, show next year's birthday (negative days + 365)
            DATEDIFF(
                STR_TO_DATE(
                    CONCAT(YEAR(CURDATE()) + 1, '-', LPAD(MONTH(m.Birthdate), 2, '0'), '-', LPAD(DAYOFMONTH(m.Birthdate), 2, '0')),
                    '%Y-%m-%d'
                ),
                CURDATE()
            ) BETWEEN 0 AND 30
        )";
        
        $sql .= " ORDER BY days_until ASC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fix: If birthday is today, ensure days_until shows 0 not negative
        foreach ($birthdays as &$b) {
            $bdayThisYear = date('Y') . '-' . date('m-d', strtotime($b['Birthdate']));
            $today = date('Y-m-d');
            if ($bdayThisYear === $today) {
                $b['days_until'] = 0;
                $b['is_today'] = true;
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $birthdays]);

} catch (PDOException $e) {
    error_log("Birthday API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
