<?php
require_once '../includes/functions.php';
requireLogin();

if (($_SESSION['user_role'] ?? '') !== 'intermediary') {
    http_response_code(403);
    exit('Permission denied');
}

$type = $_POST['type'] ?? '';
$ref = trim($_POST['ref'] ?? '');
$method = $_POST['method'] ?? '';
$comment = trim($_POST['comment'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (addIntermediaryReminder($ref, $type, $method, $comment, $phone)) {
    logAudit($_SESSION['user_name'], 'Intermediary Reminder Added', "{$method} reminder for {$type} {$ref}");
    $_SESSION['flash_message'] = $method === 'phone' ? 'Phone reminder recorded successfully.' : 'Reminder comment saved successfully.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Reminder was not saved. The letter may no longer be pending.';
    $_SESSION['flash_type'] = 'error';
}
header('Location: ../index.php?page=dashboard');
exit;
