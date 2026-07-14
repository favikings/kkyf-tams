<?php
// index.php
// This file now serves as a Router/Redirector.
// It can hand off to either the legacy root app or the v2 routed app.

// Start session for auth checks
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

require_once 'includes/db_connect.php';

function app_target(string $publicPath, string $legacyPath): string
{
    $basePath = rtrim(BASE_PATH, '/');

    if ($basePath !== '' && preg_match('#/public$#', $basePath) === 1) {
        return $basePath . $publicPath;
    }

    return $basePath . $legacyPath;
}

// 1. If User is Logged In -> Redirect to Dashboard
if (isset($_SESSION['v2_user'])) {
    header("Location: " . app_target('/dashboard', '/index.php'));
    exit;
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Super Admin') {
        header("Location: " . app_target('/dashboard', '/admin/dashboard.php'));
        exit;
    } elseif ($_SESSION['role'] === 'Tent Admin') {
        header("Location: " . app_target('/dashboard', '/tent/dashboard.php'));
        exit;
    }
}

// 2. If Not Logged In -> Redirect to the active login page
// Pass along any query parameters (like error messages)
$queryString = $_SERVER['QUERY_STRING'];
$target = app_target('/login', '/login.php');
if (!empty($queryString)) {
    $target .= "?" . $queryString;
}

header("Location: " . $target);
exit;
?>
