<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT m.Member_UUID, m.Full_Name, m.Current_Tent_ID, t.Tent_Name
        FROM Members m
        LEFT JOIN Tents t ON m.Current_Tent_ID = t.Tent_ID
        WHERE m.Full_Name LIKE ? OR m.Phone LIKE ?
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'count' => count($members), 'data' => $members]);

}
catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
