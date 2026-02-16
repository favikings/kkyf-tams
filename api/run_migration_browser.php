<?php
// api/run_migration_browser.php
require_once '../includes/db_connect.php';

echo "<h1>Birthday Privacy Migration</h1>";

try {
    $sql = "
        UPDATE Members 
        SET Birthdate = CONCAT('2000-', DATE_FORMAT(Birthdate, '%m-%d')) 
        WHERE Birthdate IS NOT NULL
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $count = $stmt->rowCount();

    echo "<p style='color: green;'><strong>Success!</strong> Updated $count records to Year 2000 privacy standard.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>