<?php
// api/public_verify_member.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? '');

if (strlen($identifier) < 2) {
    echo json_encode(['success' => false, 'error' => 'Please provide at least 2 characters.']);
    exit;
}

try {
    $sql = "
        SELECT m.Member_UUID, m.Full_Name, m.Current_Tent_ID, t.Tent_Name
        FROM Members m
        LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
        WHERE m.Full_Name LIKE ? OR m.Phone LIKE ?
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$identifier%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($members) > 0) {
        echo json_encode(['success' => true, 'members' => $members]);
    } else {
        echo json_encode(['success' => false, 'error' => 'MEMBER_NOT_FOUND', 'message' => 'We could not find a member with that name or phone number.']);
    }

} catch (PDOException $e) {
    error_log("Verify Member API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
}