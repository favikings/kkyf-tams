<?php
// api/public_verify_member.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? '');

if (empty($identifier)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a name or phone number.']);
    exit;
}

try {
    $sql = "
        SELECT m.Member_UUID, m.Full_Name, m.Current_Tent_ID, t.Tent_Name
        FROM Members m
        LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
        WHERE m.Full_Name = ? OR m.Phone = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifier, $identifier]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        echo json_encode(['success' => true, 'member' => $member]);
    } else {
        echo json_encode(['success' => false, 'error' => 'MEMBER_NOT_FOUND', 'message' => 'We could not find a member with that name or phone number.']);
    }

} catch (PDOException $e) {
    error_log("Verify Member API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
}