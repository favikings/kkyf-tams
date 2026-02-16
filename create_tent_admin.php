<?php
// create_tent_admin.php
require 'includes/db_connect.php';

try {
    $username = 'tent_admin';
    $password = 'password123';
    $role = 'Tent Admin';
    $tentId = 1; // Assuming Tent ID 1 exists (e.g. 'Amazing') from schema.sql

    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Admin_User WHERE Username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        echo "User '$username' already exists.<br>";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Admin_User (Username, Password_Hash, Role, Assigned_Tent_ID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $role, $tentId]);
        echo "User '$username' created successfully!<br>";
    }

    echo "<h3>Credentials:</h3>";
    echo "Username: <b>$username</b><br>";
    echo "Password: <b>$password</b><br>";
    echo "Role: <b>$role</b><br>";
    echo "Assigned Tent ID: <b>$tentId</b><br>";
    echo "<br><a href='login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>