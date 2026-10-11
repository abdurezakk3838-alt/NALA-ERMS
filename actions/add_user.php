<?php
// actions/add_user.php
require_once '../includes/functions.php';
requireLogin();

if (!hasPermission('users')) {
    header('Location: ../index.php?page=dashboard&error=Permission denied');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $department = trim($_POST['department'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
    if ($username === '') {
        $username = strtolower($first_name . '_' . $last_name);
    }
    $username = preg_replace('/[^a-z0-9_]/', '_', strtolower($username));

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($department)) {
        header('Location: ../index.php?page=users&error=Please fill all required fields');
        exit;
    }

    if (getUserByUsername($username)) {
        header('Location: ../index.php?page=users&error=Username already exists');
        exit;
    }

    if (getUserByEmail($email)) {
        header('Location: ../index.php?page=users&error=Email already exists');
        exit;
    }

    $data = [
        'username' => $username,
        'full_name' => $full_name,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $password,
        'role' => $role,
        'department' => $department
    ];

    $result = createUser($data);

    if ($result) {
        logAudit($_SESSION['user_name'], 'User Created', "Created user: $full_name ($role)");
        header('Location: ../index.php?page=users&success=User created successfully');
    } else {
        header('Location: ../index.php?page=users&error=Failed to create user');
    }
    exit;
} else {
    header('Location: ../index.php?page=users');
    exit;
}