<?php
// api/export_roster.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Auth Check: Require an active session and enforce "Super Admin" role
checkAuth('Super Admin');

try {
    // Determine scope based on URL parameter or default to all
    $tentId = $_GET['tent_id'] ?? null;
    
    // Base Query
    $sql = "
        SELECT 
            m.Full_Name,
            t.Tent_Name,
            m.Status,
            IFNULL(m.School, 'N/A') as School,
            IFNULL(m.Phone, 'N/A') as Phone,
            IFNULL(m.Birthdate, 'N/A') as Birthdate,
            DATE_FORMAT(m.Join_Date, '%Y-%m-%d') as Join_Date
        FROM Members m
        LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
    ";

    $params = [];
    if ($tentId) {
        $sql .= " WHERE m.Current_Tent_ID = ? ";
        $params[] = $tentId;
        
        $stmtTent = $pdo->prepare("SELECT Tent_Name FROM Tents WHERE Tent_ID = ?");
        $stmtTent->execute([$tentId]);
        $tentName = $stmtTent->fetchColumn();
        $filenamePrefix = "roster_" . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($tentName));
    } else {
        $filenamePrefix = "master_roster";
    }
    
    $sql .= " ORDER BY t.Tent_Name ASC, m.Full_Name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV Generation
    $dateStamp = date('Y_m_d_His');
    $filename = "{$filenamePrefix}_{$dateStamp}.csv";
    
    // Set HTTP headers to trigger a file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open the php://output stream
    $output = fopen('php://output', 'w');
    
    // Write the CSV header row
    fputcsv($output, ['Full Name', 'Tent', 'Status', 'School', 'Phone', 'Birthday', 'Join Date']);
    
    // Loop through the query results and write each member's data as a row
    foreach ($members as $member) {
        $birthdate = $member['Birthdate'];
        if ($birthdate !== 'N/A' && strpos($birthdate, '2000-') === 0) {
           $birthdate = date('M d', strtotime($birthdate));
        }

        fputcsv($output, [
            $member['Full_Name'],
            $member['Tent_Name'] ?? 'Unassigned',
            $member['Status'],
            $member['School'],
            $member['Phone'],
            $birthdate,
            $member['Join_Date']
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("Export Roster Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error generating export file.";
    exit;
}
?>
