<?php
// logout.php
require_once 'includes/db_connect.php';

session_start();
session_unset();
session_destroy();

header("Location: " . BASE_PATH . "/index.php");
exit;
?>