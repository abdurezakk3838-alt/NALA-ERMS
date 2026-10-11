<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

// User functions
function getUserByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getUserRole($username) {
    $user = getUserByUsername($username);
    return $user ? $user['role'] : null;
}

function getRoleConfig($role) {
    $configs = [
        'head_office' => ['title' => 'Head Office Officer', 'avatar' => 'H', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'records', 'workflow', 'archive', 'search', 'settings']],
        'branch' => ['title' => 'Branch Officer', 'avatar' => 'B', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'search', 'settings']],
        'admin' => ['title' => 'System Administrator', 'avatar' => 'A', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'records', 'workflow', 'archive', 'search', 'users', 'audit', 'reports', 'settings']],
        'dept_manager' => ['title' => 'Department Manager', 'avatar' => 'M', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'records', 'workflow', 'search', 'reports', 'settings']],
        'records_officer' => ['title' => 'Records Officer', 'avatar' => 'R', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'records', 'workflow', 'archive', 'search', 'settings']],
        'director' => ['title' => 'Director General', 'avatar' => 'D', 'permissions' => ['dashboard', 'incoming', 'outgoing', 'internal', 'workflow', 'archive', 'search', 'audit', 'reports', 'settings']],
        'staff' => ['title' => 'Staff Member', 'avatar' => 'S', 'permissions' => ['dashboard', 'outgoing', 'internal', 'search', 'settings']]
    ];
    return isset($configs[$role]) ? $configs[$role] : null;
}

function hasPermission($permission) {
    if (!isset($_SESSION['user_role'])) return false;
    $config = getRoleConfig($_SESSION['user_role']);
    return $config && in_array($permission, $config['permissions']);
}

// Generate reference numbers
function generateRefNumber($prefix = 'INC-2025-') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(ref_number, LENGTH(?) + 1) AS UNSIGNED)) as max_num 
                           FROM incoming_letters WHERE ref_number LIKE ?");
    $stmt->execute([$prefix, $prefix . '%']);
    $result = $stmt->fetch();
    $num = ($result && $result['max_num']) ? intval($result['max_num']) + 1 : 1;
    return $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
}

function generateOutRefNumber() {
    global $pdo;
    $prefix = 'OUT-2025-';
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(ref_number, LENGTH(?) + 1) AS UNSIGNED)) as max_num 
                           FROM outgoing_letters WHERE ref_number LIKE ?");
    $stmt->execute([$prefix, $prefix . '%']);
    $result = $stmt->fetch();
    $num = ($result && $result['max_num']) ? intval($result['max_num']) + 1 : 1;
    return $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
}

function generateInternalRefNumber() {
    global $pdo;
    $prefix = 'INT-2025-';
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(ref_number, LENGTH(?) + 1) AS UNSIGNED)) as max_num 
                           FROM internal_memos WHERE ref_number LIKE ?");
    $stmt->execute([$prefix, $prefix . '%']);
    $result = $stmt->fetch();
    $num = ($result && $result['max_num']) ? intval($result['max_num']) + 1 : 1;
    return $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
}

// CRUD operations for incoming letters
function createIncomingLetter($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO incoming_letters 
        (ref_number, sender_unique_num, title, description, sender_name, receiver_name, receiver_email, 
         status, received_date, file_name, file_data, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['ref_number'],
        $data['sender_unique_num'],
        $data['title'],
        $data['description'],
        $data['sender_name'],
        $data['receiver_name'],
        $data['receiver_email'],
        $data['status'] ?? 'pending',
        $data['received_date'],
        $data['file_name'] ?? null,
        $data['file_data'] ?? null,
        $_SESSION['user_name'] ?? 'System'
    ]);
}

function getIncomingLetters($filters = []) {
    global $pdo;
    $sql = "SELECT * FROM incoming_letters WHERE 1=1";
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['receiver'])) {
        $sql .= " AND receiver_name = ?";
        $params[] = $filters['receiver'];
    }
    if (!empty($filters['sender'])) {
        $sql .= " AND sender_name = ?";
        $params[] = $filters['sender'];
    }
    if (!empty($filters['ref'])) {
        $sql .= " AND ref_number LIKE ?";
        $params[] = '%' . $filters['ref'] . '%';
    }
    if (!empty($filters['date_from'])) {
        $sql .= " AND received_date >= ?";
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= " AND received_date <= ?";
        $params[] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getIncomingLetter($ref) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM incoming_letters WHERE ref_number = ?");
    $stmt->execute([$ref]);
    return $stmt->fetch();
}

function updateIncomingLetter($ref, $data) {
    global $pdo;
    $fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'ref_number') {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    $params[] = $ref;
    
    $sql = "UPDATE incoming_letters SET " . implode(', ', $fields) . " WHERE ref_number = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// CRUD operations for outgoing letters
function createOutgoingLetter($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO outgoing_letters 
        (ref_number, title, description, sender_name, receiver_name, receiver_email, 
         classification, status, date, file_name, file_data, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['ref_number'],
        $data['title'],
        $data['description'] ?? '',
        $data['sender_name'],
        $data['receiver_name'],
        $data['receiver_email'] ?? '',
        $data['classification'] ?? '1.01 General Administration',
        $data['status'] ?? 'pending_approval',
        $data['date'],
        $data['file_name'] ?? null,
        $data['file_data'] ?? null,
        $_SESSION['user_name'] ?? 'System'
    ]);
}

function getOutgoingLetters($filters = []) {
    global $pdo;
    $sql = "SELECT * FROM outgoing_letters WHERE 1=1";
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['sender'])) {
        $sql .= " AND sender_name = ?";
        $params[] = $filters['sender'];
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOutgoingLetter($ref) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM outgoing_letters WHERE ref_number = ?");
    $stmt->execute([$ref]);
    return $stmt->fetch();
}

function updateOutgoingLetter($ref, $data) {
    global $pdo;
    $fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'ref_number') {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    $params[] = $ref;
    
    $sql = "UPDATE outgoing_letters SET " . implode(', ', $fields) . " WHERE ref_number = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// CRUD operations for internal memos
function createInternalMemo($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO internal_memos 
        (ref_number, title, description, sender_name, receiver_name, receiver_email, 
         status, date, file_name, file_data, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['ref_number'],
        $data['title'],
        $data['description'] ?? '',
        $data['sender_name'],
        $data['receiver_name'],
        $data['receiver_email'] ?? '',
        $data['status'] ?? 'pending',
        $data['date'],
        $data['file_name'] ?? null,
        $data['file_data'] ?? null,
        $_SESSION['user_name'] ?? 'System'
    ]);
}

function getInternalMemos($filters = []) {
    global $pdo;
    $sql = "SELECT * FROM internal_memos WHERE 1=1";
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['receiver'])) {
        $sql .= " AND receiver_name = ?";
        $params[] = $filters['receiver'];
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getInternalMemo($ref) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM internal_memos WHERE ref_number = ?");
    $stmt->execute([$ref]);
    return $stmt->fetch();
}

function updateInternalMemo($ref, $data) {
    global $pdo;
    $fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'ref_number') {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    $params[] = $ref;
    
    $sql = "UPDATE internal_memos SET " . implode(', ', $fields) . " WHERE ref_number = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// Audit logging
function logAudit($user, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, user_agent) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $role = $_SESSION['user_role'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $stmt->execute([$user, $role, $action, $details, $ip, $ua]);
}

function getAuditLogs($limit = 100) {
    global $pdo;
    $limit = max(0, (int) $limit);
    $stmt = $pdo->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute();
    return $stmt->fetchAll();
}

// User management
function createUser($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password_hash, role, department) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    return $stmt->execute([
        $data['username'],
        $data['full_name'],
        $data['email'],
        $password_hash,
        $data['role'],
        $data['department']
    ]);
}

function updateUser($username, $data) {
    global $pdo;
    $fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'username') {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    $params[] = $username;
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function deleteUser($username) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND role != 'admin'");
    return $stmt->execute([$username]);
}

function getUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Search function
function searchRecords($criteria) {
    global $pdo;
    $results = [];
    $searchTerm = '%' . $criteria['query'] . '%';
    
    // Search incoming
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'incoming') {
        $sql = "SELECT *, 'Incoming Letter' as doc_type FROM incoming_letters 
                WHERE ref_number LIKE ? OR title LIKE ? OR sender_name LIKE ? OR receiver_name LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = array_merge($results, $stmt->fetchAll());
    }
    
    // Search outgoing
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'outgoing') {
        $sql = "SELECT *, 'Outgoing Letter' as doc_type FROM outgoing_letters 
                WHERE ref_number LIKE ? OR title LIKE ? OR sender_name LIKE ? OR receiver_name LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = array_merge($results, $stmt->fetchAll());
    }
    
    // Search internal
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'internal') {
        $sql = "SELECT *, 'Internal Memo' as doc_type FROM internal_memos 
                WHERE ref_number LIKE ? OR title LIKE ? OR sender_name LIKE ? OR receiver_name LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results = array_merge($results, $stmt->fetchAll());
    }
    
    return $results;
}

// Dashboard statistics
function getDashboardStats() {
    global $pdo;
    $stats = [];
    
    // Count incoming
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incoming_letters");
    $stats['incoming_count'] = $stmt->fetch()['count'];
    
    // Count outgoing
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM outgoing_letters");
    $stats['outgoing_count'] = $stmt->fetch()['count'];
    
    // Count internal
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM internal_memos");
    $stats['internal_count'] = $stmt->fetch()['count'];
    
    // Count pending approvals
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incoming_letters WHERE status IN ('pending', 'pending_approval')");
    $stats['pending_incoming'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM outgoing_letters WHERE status = 'pending_approval'");
    $stats['pending_outgoing'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM internal_memos WHERE status = 'pending'");
    $stats['pending_internal'] = $stmt->fetch()['count'];
    
    // Count approved
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incoming_letters WHERE status = 'approved'");
    $stats['approved_incoming'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM outgoing_letters WHERE status = 'approved'");
    $stats['approved_outgoing'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM internal_memos WHERE status = 'approved'");
    $stats['approved_internal'] = $stmt->fetch()['count'];
    
    return $stats;
}

// Get recent records
function getRecentRecords($limit = 5) {
    global $pdo;
    $records = [];
    $limit = max(0, (int) $limit);
    
    $stmt = $pdo->prepare("SELECT ref_number, title, sender_name, receiver_name, status, received_date as date, 'Incoming' as type 
                           FROM incoming_letters ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute();
    $records = array_merge($records, $stmt->fetchAll());
    
    $stmt = $pdo->prepare("SELECT ref_number, title, sender_name, receiver_name, status, date, 'Outgoing' as type 
                           FROM outgoing_letters ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute();
    $records = array_merge($records, $stmt->fetchAll());
    
    $stmt = $pdo->prepare("SELECT ref_number, title, sender_name, receiver_name, status, date, 'Internal' as type 
                           FROM internal_memos ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute();
    $records = array_merge($records, $stmt->fetchAll());
    
    usort($records, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($records, 0, $limit);
}

// Helper: Get avatar HTML
function getAvatarHTML($username, $size = '36px') {
    $user = getUserByUsername($username);
    if (!$user) {
        return '<div class="ua-avatar" style="width:' . $size . ';height:' . $size . ';background:#0a1e3a;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:600;">' . strtoupper(substr($username, 0, 1)) . '</div>';
    }
    if ($user['photo']) {
        return '<div class="ua-avatar" style="width:' . $size . ';height:' . $size . ';overflow:hidden;border-radius:50%;flex-shrink:0;"><img src="' . $user['photo'] . '" alt="' . $user['full_name'] . '" style="width:100%;height:100%;object-fit:cover;" /></div>';
    } else {
        return '<div class="ua-avatar" style="width:' . $size . ';height:' . $size . ';background:#0a1e3a;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:600;flex-shrink:0;">' . strtoupper(substr($user['full_name'], 0, 1)) . '</div>';
    }
}

// Status pill HTML
function getStatusPill($status) {
    $classes = [
        'pending' => 'pending',
        'pending_approval' => 'pending_approval',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'in-process' => 'in-process',
        'archived' => 'archived',
        'dispatched' => 'dispatched',
        'completed' => 'completed',
        'registered' => 'registered'
    ];
    $class = isset($classes[$status]) ? $classes[$status] : 'pending';
    return '<span class="status-pill ' . $class . '">' . str_replace('_', ' ', $status) . '</span>';
}

// File upload helper
function uploadFile($file, $target_dir = '../uploads/') {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file['tmp_name']);
    if ($check !== false) {
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $file_name;
        }
    } elseif (in_array($file_type, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $file_name;
        }
    }
    return false;
}

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_name']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>
