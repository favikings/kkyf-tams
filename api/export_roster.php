<?php
// api/export_roster.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Auth Check: Require an active session and enforce "Super Admin" role
checkAuth('Super Admin');

try {
    // Database Query
    $sql = "
        SELECT 
            m.Full_Name,
            t.Tent_Name,
            m.Status,
            m.School,
            m.Phone,
            m.Birthdate,
            DATE(m.Join_Date) as Join_Date
        FROM Members m
        LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
        ORDER BY t.Tent_Name ASC, m.Full_Name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV Generation
    $filename = "master_roster_" . date('Y_m_d') . ".csv";
    
    // Set HTTP headers to trigger a file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open the php://output stream
    $output = fopen('php://output', 'w');
    
    // Write the CSV header row
    fputcsv($output, ['Full Name', 'Tent', 'Status', 'School', 'Phone', 'Birthday', 'Join Date']);
    
    // Loop through the query results and write each member's data as a row
    foreach ($members as $member) {
        fputcsv($output, [
            $member['Full_Name'],
            $member['Tent_Name'] ?? 'Unassigned',
            $member['Status'],
            $member['School'],
            $member['Phone'],
            $member['Birthdate'],
            $member['Join_Date']
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("Export Roster Error: " . $e->getMessage());
    // Redirect back to roster.php with an error parameter
    header("Location: " . BASE_PATH . "/admin/roster.php?error=export_failed");
    exit;
}
?>
