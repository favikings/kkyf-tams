<?php
// index.php
// This file now serves as a Router/Redirector.
// The actual Login UI is at login.php

// Start session for auth checks
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

require_once 'includes/db_connect.php';

// 1. If User is Logged In -> Redirect to Dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Super Admin') {
        header("Location: " . BASE_PATH . "/admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'Tent Admin') {
        header("Location: " . BASE_PATH . "/tent/dashboard.php");
        exit;
    }
}

// 2. If Not Logged In -> Redirect to Login Page (New UI)
// Pass along any query parameters (like error messages)
$queryString = $_SERVER['QUERY_STRING'];
$target = BASE_PATH . "/login.php";
if (!empty($queryString)) {
    $target .= "?" . $queryString;
}

header("Location: " . $target);
exit;
?>