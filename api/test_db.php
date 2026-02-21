<?php
// api/test_db.php
require_once '../includes/db_connect.php';

echo "<h1>Database Connection Test</h1>";

if (isset($pdo)) {
    echo "<h2 style='color: green;'>✅ SUCCESS! Connected to Database.</h2>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
} else {
    echo "<h2 style='color: red;'>❌ FAILED: Connection variable not found.</h2>";
}
?>