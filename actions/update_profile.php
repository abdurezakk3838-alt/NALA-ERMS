<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=settings');
    exit;
}

$username = $_SESSION['username'] ?? '';
$user = getUserByUsername($username);

if (!$user) {
    $_SESSION['flash_message'] = 'User not found.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ../index.php?page=settings');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if ($full_name === '' || $email === '') {
    $_SESSION['flash_message'] = 'Full name and email are required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ../index.php?page=settings');
    exit;
}

$data = [
    'full_name' => $full_name,
    'email' => $email,
    'department' => $department,
];

if (!empty($_POST['remove_profile_photo'])) {
    $data['photo'] = null;
}

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload = $_FILES['profile_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_message'] = 'The profile photo could not be uploaded.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    if ($upload['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash_message'] = 'Profile photo must be 2 MB or smaller.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    $imageInfo = @getimagesize($upload['tmp_name']);
    if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedTypes, true)) {
        $_SESSION['flash_message'] = 'Please upload a valid JPG, PNG, GIF, or WebP image.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    $imageData = file_get_contents($upload['tmp_name']);
    if ($imageData === false) {
        $_SESSION['flash_message'] = 'Unable to read the selected profile photo.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    $data['photo'] = 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageData);
}

if ($new_password !== '') {
    if ($current_password === '') {
        $_SESSION['flash_message'] = 'Please enter your current password to change it.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    if (!password_verify($current_password, $user['password_hash'])) {
        $_SESSION['flash_message'] = 'Current password is incorrect.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=settings');
        exit;
    }

    $data['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
}

$result = updateUser($username, $data);
if ($result) {
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_department'] = $department;
    if (array_key_exists('photo', $data)) $_SESSION['user_photo'] = $data['photo'];
    $_SESSION['flash_message'] = 'Profile updated successfully.';
    $_SESSION['flash_type'] = 'success';
    logAudit($full_name, 'Profile Updated', 'Updated profile and security settings');
} else {
    $_SESSION['flash_message'] = 'Unable to update profile.';
    $_SESSION['flash_type'] = 'error';
}

header('Location: ../index.php?page=settings');
exit;
