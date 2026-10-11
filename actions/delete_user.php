<?php
// actions/delete_user.php
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

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

$user = getUserByUsername($username);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ($user['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete admin user']);
    exit;
}

if ($user['username'] === $_SESSION['username']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

$result = deleteUser($username);

if ($result) {
    logAudit($_SESSION['user_name'], 'User Deleted', "Deleted user: $username");
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
}