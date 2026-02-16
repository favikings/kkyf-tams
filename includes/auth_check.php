<?php
// includes/auth_check.php

function checkAuth($requiredRole = null)
{
    // Session should be started in the including file (usually header or top of page)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Check if logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_PATH . "/index.php");
        exit;
    }

    // 2. Check Role (if specific role required)
    if ($requiredRole !== null) {
        // Hierarchical Access: Super Admin can access Tent Admin pages
        if ($_SESSION['role'] === 'Super Admin') {
            // Super Admin is allowed everywhere
        } elseif ($_SESSION['role'] !== $requiredRole) {
            // Strict role enforcement for others
            header("Location: " . BASE_PATH . "/unauthorized.php");
            exit;
        }
    }

    // 3. Tent ID Scope Check (for Tent Admins)
    if ($_SESSION['role'] === 'Tent Admin' && isset($_GET['tent_id'])) {
        if ($_GET['tent_id'] != $_SESSION['assigned_tent_id']) {
            header("Location: " . BASE_PATH . "/unauthorized.php?msg=tent_mismatch");
            exit;
        }
    }
}
?>