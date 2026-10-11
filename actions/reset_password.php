<?php
require_once '../includes/functions.php';
requireLogin();

if (!hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = $_POST['username'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (empty($username) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Username and new password are required']);
    exit;
}

$user = getUserByUsername($username);
if (!$user || $user['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot reset this account']);
    exit;
}

$result = resetUserPassword($username, $newPassword);
if ($result) {
    logAudit($_SESSION['user_name'], 'Password Reset', "Reset password for: $username");
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
}
