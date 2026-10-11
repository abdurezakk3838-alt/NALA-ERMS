<?php
// logout.php
require_once 'includes/functions.php';

 $loggedOutName = $_SESSION['user_name'] ?? 'User';
if (isset($_SESSION['user_name'])) {
    logAudit($_SESSION['user_name'], 'Logout Successful', "{$loggedOutName} signed out successfully from the NALA ERMS.");
}

session_destroy();
session_start();
$_SESSION['flash_message'] = 'Logout successful, ' . $loggedOutName . '! Thank you for your work today — keep moving forward.';
$_SESSION['flash_type'] = 'success';
header('Location: login.php');
exit;
?>
