<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';

function ensureArchivistRoleSchema() {
    global $pdo;
    static $done = false;
    if ($done) return;
    try {
        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        $type = strtolower($column['Type'] ?? '');
        if (strpos($type, 'enum(') === 0 && strpos($type, "'archivist'") === false) {
            $pdo->exec("ALTER TABLE users MODIFY role ENUM('head_office','branch','admin','dept_manager','records_officer','director','intermediary','staff','archivist') DEFAULT 'staff'");
        }
    } catch (Throwable $e) {
        // Keep the application usable on installations with a custom schema.
    }
    $done = true;
}

ensureArchivistRoleSchema();

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
        'intermediary' => ['title' => 'Intermediary Officer', 'avatar' => 'I', 'permissions' => ['dashboard']],
        'archivist' => ['title' => 'Archivist', 'avatar' => 'A', 'permissions' => ['dashboard', 'archive', 'settings']],
        'staff' => ['title' => 'Staff Member', 'avatar' => 'S', 'permissions' => ['dashboard', 'outgoing', 'internal', 'search', 'settings']]
    ];
    return isset($configs[$role]) ? $configs[$role] : null;
}

function getDepartments() {
    return [
        'Head of the Office',
        'Ethics Monitoring Executive',
        'Women and Social Affairs Inclusion Implementation Executive',
        'Management Leadership Executive',
        'Institutional Transformation Executive',
        'Archives and Libraries Training and Consultancy Lead Executive',
        'Document and Records Management Lead Executive',
        'Library Information Resources Management Lead Executive',
        'Library Services Lead Executive',
        'Competency and Human Resources Management Executive',
        'Procurement and Finance Executive',
        'Strategic Affairs Executive',
        'Public Relations and Communication Executive',
        'Audit Executive',
        'Information and Communication Technology (ICT) Executive',
        'Basic Services Executive'
    ];
}

function hasPermission($permission) {
    // Every authenticated user must be able to read their own notifications.
    if ($permission === 'notifications') return isLoggedIn();
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
    ensureIncomingSchema();
    $stmt = $pdo->prepare("INSERT INTO incoming_letters 
        (ref_number, sender_unique_num, title, description, sender_name, receiver_name, receiver_email, 
         classification, security_level, keywords, status, received_date, received_time, file_name, file_data, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['ref_number'],
        $data['sender_unique_num'],
        $data['title'],
        $data['description'],
        $data['sender_name'],
        $data['receiver_name'],
        $data['receiver_email'],
        $data['classification'] ?? '1.01 General Administration',
        $data['security_level'] ?? 'Official',
        $data['keywords'] ?? '',
        $data['status'] ?? 'pending',
        $data['received_date'],
        $data['received_time'],
        $data['file_name'] ?? null,
        $data['file_data'] ?? null,
        $_SESSION['user_name'] ?? 'System'
    ]);
}

function getIncomingLetters($filters = []) {
    global $pdo;
    ensureIncomingSchema();
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
    return array_values(array_filter($stmt->fetchAll(), static function ($letter) {
        return canViewDocument($letter, 'incoming');
    }));
}

function getIncomingLetter($ref) {
    global $pdo;
    ensureIncomingSchema();
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
    ensureOutgoingWorkflowColumns();
    $stmt = $pdo->prepare("INSERT INTO outgoing_letters 
        (ref_number, sender_unique_num, title, description, sender_name, receiver_name, receiver_email, 
         classification, security_level, priority, keywords, status, date, file_name, file_data, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['ref_number'],
        $data['sender_unique_num'] ?? '',
        $data['title'],
        $data['description'] ?? '',
        $data['sender_name'],
        $data['receiver_name'],
        $data['receiver_email'] ?? '',
        $data['classification'] ?? '1.01 General Administration',
        $data['security_level'] ?? 'Official',
        $data['priority'] ?? 'Normal',
        $data['keywords'] ?? '',
        $data['status'] ?? 'pending_approval',
        $data['date'],
        $data['file_name'] ?? null,
        $data['file_data'] ?? null,
        $_SESSION['user_name'] ?? 'System'
    ]);
}

function getOutgoingLetters($filters = []) {
    global $pdo;
    ensureOutgoingWorkflowColumns();
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
    return array_values(array_filter($stmt->fetchAll(), static function ($letter) {
        return canViewDocument($letter, 'outgoing');
    }));
}

function getOutgoingLetter($ref) {
    global $pdo;
    ensureOutgoingWorkflowColumns();
    $stmt = $pdo->prepare("SELECT * FROM outgoing_letters WHERE ref_number = ?");
    $stmt->execute([$ref]);
    return $stmt->fetch();
}

function updateOutgoingLetter($ref, $data) {
    global $pdo;
    ensureOutgoingWorkflowColumns();
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
    return array_values(array_filter($stmt->fetchAll(), static function ($memo) {
        return canViewDocument($memo, 'internal');
    }));
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

// Notification functions
function ensureNotificationsTable() {
    global $pdo;
    static $ready = false;
    if ($ready) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'workflow',
        is_read TINYINT(1) DEFAULT 0,
        link VARCHAR(500) DEFAULT '#',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user (user_id)
    )");
    $ready = true;
}

function createNotification($userId, $title, $message, $type = 'workflow', $link = '#') {
    global $pdo;
    ensureNotificationsTable();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([(int) $userId, $title, $message, $type, $link]);
}

function notifyUsersByRole($roles, $title, $message, $type = 'workflow', $link = '#') {
    global $pdo;
    $roles = (array) $roles;
    if (!$roles) return false;
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    // Older installations do not have users.is_active yet. The role filter is
    // still safe and keeps workflow notifications working on both schemas.
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role IN ($placeholders)");
    $stmt->execute($roles);
    foreach ($stmt->fetchAll() as $user) {
        createNotification($user['id'], $title, $message, $type, $link);
    }
    return true;
}

function notifyUser($userId, $title, $message, $type = 'workflow', $link = '#') {
    return createNotification($userId, $title, $message, $type, $link);
}

function ensureWorkflowTable() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS workflow_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        trigger_event VARCHAR(100) NOT NULL,
        action_type VARCHAR(100) NOT NULL,
        action_config JSON NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    return true;
}

function getWorkflowRules() {
    global $pdo;
    ensureWorkflowTable();
    return $pdo->query('SELECT * FROM workflow_rules ORDER BY created_at DESC, id DESC')->fetchAll();
}

function recordOutgoingWorkflow($ref, $stage, $event, $remark = '') {
    global $pdo;
    ensureOutgoingWorkflowHistoryTable();
    $stmt = $pdo->prepare('INSERT INTO outgoing_workflow_history (ref_number, stage, event_label, remark, acted_by) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$ref, $stage, $event, $remark, $_SESSION['user_name'] ?? 'System']);
}

function ensureOutgoingWorkflowHistoryTable() {
    global $pdo;
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS outgoing_workflow_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_number VARCHAR(50) NOT NULL,
        stage VARCHAR(100) NOT NULL,
        event_label VARCHAR(255) NOT NULL,
        remark TEXT,
        acted_by VARCHAR(200),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_outgoing_workflow_ref (ref_number)
    )");
    $done = true;
}

function getOutgoingWorkflowHistory($ref) {
    global $pdo;
    try {
        $stmt = $pdo->prepare('SELECT * FROM outgoing_workflow_history WHERE ref_number = ? ORDER BY created_at ASC, id ASC');
        $stmt->execute([$ref]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function canReviewDocument($document, $type = '') {
    if (!is_array($document) || !hasPermission('workflow')) return false;
    $status = $document['status'] ?? '';
    if (!in_array($status, ['pending', 'in-process', 'pending_approval'], true)) return false;
    // Review belongs to the assigned receiver. The sender and administrators
    // cannot bypass this ownership rule.
    if (($document['receiver_name'] ?? '') !== ($_SESSION['user_name'] ?? '')) return false;
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['admin', 'director', 'dept_manager', 'head_office', 'records_officer'], true);
}

function ensureIncomingSchema() {
    global $pdo;
    static $done = false;
    if ($done) return;
    $check = $pdo->query("SHOW COLUMNS FROM incoming_letters LIKE 'security_level'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE incoming_letters ADD COLUMN security_level VARCHAR(30) NULL DEFAULT 'Official' AFTER classification");
    }
    $timeCheck = $pdo->query("SHOW COLUMNS FROM incoming_letters LIKE 'received_time'")->fetch();
    if (!$timeCheck) {
        $pdo->exec("ALTER TABLE incoming_letters ADD COLUMN received_time TIME NULL AFTER received_date");
    }
    $done = true;
}

function getIntermediaryPendingLetters() {
    global $pdo;
    $rows = [];
    $sources = [
        ['incoming_letters', 'incoming', 'Incoming Letter', 'received_date'],
        ['outgoing_letters', 'outgoing', 'Outgoing Letter', 'date'],
        ['internal_memos', 'internal', 'Internal Memo', 'date']
    ];
    foreach ($sources as [$table, $documentType, $label, $dateColumn]) {
        $sql = "SELECT *, '{$label}' AS type_label, '{$documentType}' AS document_type,
                {$dateColumn} AS sent_date,
                TIMESTAMPDIFF(DAY, created_at, NOW()) AS pending_days,
                MOD(TIMESTAMPDIFF(HOUR, created_at, NOW()), 24) AS pending_hours
                FROM {$table}
                WHERE status IN ('pending', 'in-process', 'pending_approval')
                ORDER BY created_at DESC";
        foreach ($pdo->query($sql)->fetchAll() as $row) $rows[] = $row;
    }
    usort($rows, static function ($a, $b) { return strtotime($b['created_at']) <=> strtotime($a['created_at']); });
    return $rows;
}

function addIntermediaryReminder($ref, $type, $method, $comment = '', $phone = '') {
    global $pdo;
    $tables = ['incoming' => 'incoming_letters', 'outgoing' => 'outgoing_letters', 'internal' => 'internal_memos'];
    if (!isset($tables[$type]) || !in_array($method, ['comment', 'phone'], true) || trim($ref) === '') return false;
    $stmt = $pdo->prepare("SELECT ref_number, title, receiver_name, receiver_email FROM {$tables[$type]} WHERE ref_number = ? AND status IN ('pending', 'in-process', 'pending_approval') LIMIT 1");
    $stmt->execute([$ref]);
    $document = $stmt->fetch();
    if (!$document) return false;
    $pdo->exec("CREATE TABLE IF NOT EXISTS intermediary_reminders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_number VARCHAR(50) NOT NULL,
        document_type VARCHAR(20) NOT NULL,
        intermediary_id INT NULL,
        reminder_method VARCHAR(20) NOT NULL,
        comment TEXT,
        phone_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_intermediary_ref (ref_number)
    )");
    $stmt = $pdo->prepare('INSERT INTO intermediary_reminders (ref_number, document_type, intermediary_id, reminder_method, comment, phone_number) VALUES (?, ?, ?, ?, ?, ?)');
    $saved = $stmt->execute([$ref, $type, (int) ($_SESSION['user_id'] ?? 0), $method, $comment, $phone]);
    if ($saved) {
        $message = $method === 'phone'
            ? "The Intermediary Officer recorded a phone reminder regarding {$ref}."
            : ($comment !== '' ? "Reminder regarding {$ref}: {$comment}" : "The Intermediary Officer sent a reminder regarding {$ref}.");
        notifyReceiverAboutDocument($document['receiver_name'], $message, $ref, $type, $document['receiver_email'] ?? '', 'reminder');
    }
    return $saved;
}

function generateSystemSignature($ref, $user, $signedAt) {
    $safeRef = htmlspecialchars((string) $ref, ENT_QUOTES, 'UTF-8');
    $safeUser = htmlspecialchars((string) $user, ENT_QUOTES, 'UTF-8');
    $safeDate = htmlspecialchars((string) $signedAt, ENT_QUOTES, 'UTF-8');
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='640' height='150' viewBox='0 0 640 150'><rect width='100%' height='100%' fill='white'/><path d='M25 105 C80 20 105 135 155 55 S235 80 270 45' fill='none' stroke='#0a1e3a' stroke-width='5' stroke-linecap='round'/><text x='295' y='65' font-family='cursive' font-size='28' fill='#0a1e3a'>NALA ERMS</text><text x='295' y='95' font-family='Arial' font-size='14' fill='#475569'>Digitally signed • {$safeRef} • {$safeUser}</text><text x='25' y='135' font-family='Arial' font-size='11' fill='#64748b'>{$safeDate}</text></svg>";
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function canForwardOutgoingLetter($letter) {
    return is_array($letter) && hasPermission('workflow')
        && in_array($_SESSION['user_role'] ?? '', ['admin', 'head_office'], true)
        && ($letter['status'] ?? '') === 'pending_approval'
        && (($letter['workflow_stage'] ?? 'head_review') === 'head_review');
}

function canApproveOutgoingLetter($letter) {
    return is_array($letter) && hasPermission('workflow')
        && in_array($_SESSION['user_role'] ?? '', ['admin', 'director'], true)
        && ($letter['status'] ?? '') === 'pending_approval'
        && (($letter['workflow_stage'] ?? '') === 'director_approval');
}

function canRegisterOutgoingLetter($letter) {
    return is_array($letter) && hasPermission('records')
        && in_array($_SESSION['user_role'] ?? '', ['admin', 'records_officer'], true)
        && ($letter['status'] ?? '') === 'approved'
        && (($letter['workflow_stage'] ?? '') === 'records_registration');
}

function canDispatchOutgoingLetter($letter) {
    return is_array($letter) && hasPermission('records')
        && in_array($_SESSION['user_role'] ?? '', ['admin', 'records_officer'], true)
        && ($letter['status'] ?? '') === 'approved'
        && (($letter['workflow_stage'] ?? '') === 'ready_for_dispatch');
}

function generateOutgoingRegistrationReference() {
    global $pdo;
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM outgoing_letters WHERE registration_reference LIKE ?");
    try { $stmt->execute(["REG-$year-%"]); $number = (int) $stmt->fetchColumn() + 1; }
    catch (Throwable $e) { $number = 1; }
    return 'REG-' . $year . '-' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
}

function generateOfficialDigitalStamp($ref, $user, $signedAt) {
    return 'NALA ERMS | Official Digital Stamp | ' . $ref . ' | ' . $user . ' | ' . $signedAt;
}

function archiveDocument($sourceTable, $document) {
    global $pdo;
    $allowed = ['incoming_letters', 'outgoing_letters', 'internal_memos'];
    if (!in_array($sourceTable, $allowed, true) || !is_array($document) || empty($document['ref_number'])) return false;
    ensureArchiveTable();
    $stmt = $pdo->prepare('INSERT INTO archive_records (original_table, original_id, ref_number, title, archived_data, archived_by, retention_period, expiry_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE original_id = VALUES(original_id), title = VALUES(title), archived_data = VALUES(archived_data), archived_by = VALUES(archived_by), archived_date = CURRENT_TIMESTAMP');
    $expiry = date('Y-m-d', strtotime('+365 days'));
    return $stmt->execute([$sourceTable, (int) ($document['id'] ?? 0), $document['ref_number'], $document['title'] ?? '', json_encode($document, JSON_UNESCAPED_UNICODE), $_SESSION['user_name'] ?? 'System', 365, $expiry]);
}

function notifyArchivistsAboutDocument($document, $type, $outcome) {
    $documentTypes = [
        'incoming' => 'incoming letter',
        'outgoing' => 'outgoing letter',
        'internal' => 'internal memo'
    ];
    if (!is_array($document) || empty($document['ref_number']) || !isset($documentTypes[$type])) return false;

    $title = (string) ($document['title'] ?? 'Untitled document');
    $ref = (string) $document['ref_number'];
    $reviewer = (string) ($_SESSION['user_name'] ?? 'A reviewer');
    $message = ucfirst($documentTypes[$type]) . ' "' . $title . '" (Ref: ' . $ref . ') was '
        . $outcome . ' by ' . $reviewer . ' on ' . date('d M Y, H:i')
        . ' and is available in the Archive.';
    return notifyUsersByRole(
        ['archivist'],
        ucfirst($documentTypes[$type]) . ' ' . ucfirst($outcome) . ' and Archived',
        $message,
        'archive',
        'index.php?page=archive'
    );
}

function getArchivedRecords() {
    global $pdo;
    ensureArchiveTable();
    $sql = "SELECT a.*, JSON_UNQUOTE(JSON_EXTRACT(a.archived_data, '$.status')) AS document_status
            FROM archive_records a ORDER BY a.archived_date DESC, a.id DESC";
    return $pdo->query($sql)->fetchAll();
}

function canViewDocument($document, $sourceTable = '') {
    if (!is_array($document)) return false;
    if (in_array($sourceTable, ['incoming_letters', 'outgoing_letters', 'internal_memos'], true)) {
        $role = $_SESSION['user_role'] ?? '';
        $userName = trim((string) ($_SESSION['user_name'] ?? ''));
        return in_array($role, ['admin', 'intermediary'], true)
            || ($userName !== '' && (
                trim((string) ($document['sender_name'] ?? '')) === $userName
                || trim((string) ($document['receiver_name'] ?? '')) === $userName
            ))
            || hasPermission('archive');
    }
    $role = $_SESSION['user_role'] ?? '';
    if (in_array($role, ['admin', 'intermediary'], true)) return true;
    $userName = trim((string) ($_SESSION['user_name'] ?? ''));
    return $userName !== '' && (
        trim((string) ($document['sender_name'] ?? '')) === $userName
        || trim((string) ($document['receiver_name'] ?? '')) === $userName
    ) || (
        $sourceTable === 'outgoing'
        && $role === 'records_officer'
        && ($document['status'] ?? '') === 'approved'
        && in_array(($document['workflow_stage'] ?? ''), ['records_registration', 'ready_for_dispatch'], true)
    );
}

function ensureArchiveTable() {
    global $pdo;
    static $done = false;
    if ($done) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS archive_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_table VARCHAR(50) NOT NULL,
        original_id INT NOT NULL DEFAULT 0,
        ref_number VARCHAR(50) NOT NULL,
        title VARCHAR(500),
        archived_data JSON NOT NULL,
        archived_by VARCHAR(200),
        archived_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        retention_period INT DEFAULT 365,
        expiry_date DATE,
        UNIQUE KEY uq_archive_document (original_table, ref_number)
    )");
    $done = true;
}

function ensureOutgoingWorkflowColumns() {
    global $pdo;
    static $done = false;
    if ($done) return;
    $classification = $pdo->query("SHOW COLUMNS FROM outgoing_letters LIKE 'classification'")->fetch();
    if ($classification && stripos($classification['Type'] ?? '', 'enum(') === 0) {
        // Older databases used a short enum (General/Confidential/etc.), while
        // the current form stores the full records classification code.
        $pdo->exec("ALTER TABLE outgoing_letters MODIFY classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration'");
    }
    $columns = [
        'workflow_stage' => 'VARCHAR(100) NULL', 'digital_stamp' => 'LONGTEXT NULL',
        'head_reviewed_by' => 'VARCHAR(200) NULL', 'head_reviewed_at' => 'DATETIME NULL',
        'head_review_remark' => 'TEXT NULL', 'registration_reference' => 'VARCHAR(100) NULL',
        'registered_by' => 'VARCHAR(200) NULL', 'registered_at' => 'DATETIME NULL',
        'dispatch_method' => 'VARCHAR(30) NULL', 'dispatch_recipient' => 'VARCHAR(500) NULL',
        'dispatch_reference' => 'VARCHAR(100) NULL', 'dispatched_by' => 'VARCHAR(200) NULL',
        'dispatched_at' => 'DATETIME NULL', 'sender_unique_num' => 'VARCHAR(100) NULL'
    ];
    foreach ($columns as $name => $definition) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outgoing_letters' AND COLUMN_NAME = ?");
        $check->execute([$name]);
        if (!(int) $check->fetchColumn()) $pdo->exec("ALTER TABLE outgoing_letters ADD COLUMN `$name` $definition");
    }
    $done = true;
}

function notifyReceiverAboutDocument($receiverName, $title, $refNumber, $type = 'incoming', $receiverEmail = '', $notificationKind = 'document', $senderName = '') {
    global $pdo;
    $receiverEmail = trim((string) $receiverEmail);
    $documentTypes = [
        'incoming' => 'incoming letter',
        'outgoing' => 'outgoing letter',
        'internal' => 'internal memo'
    ];
    $documentType = $documentTypes[$type] ?? 'document';
    $receivedAt = date('d M Y, H:i');
    $senderName = trim((string) $senderName);
    $message = $notificationKind === 'reminder'
        ? "{$title} ({$refNumber})"
        : "A new {$documentType} \"{$title}\" (Ref: {$refNumber}) has been assigned to you"
            . ($senderName !== '' ? " by {$senderName}" : '')
            . " on {$receivedAt}. Please review the document and take the required action.";
    $user = null;

    // Registered recipients get an in-app notification. An email address is
    // also enough to notify an external recipient who has no account yet.
    if ($receiverName !== '' || $receiverEmail !== '') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (full_name = ? AND ? <> '') OR (email = ? AND ? <> '') LIMIT 1");
        $stmt->execute([$receiverName, $receiverName, $receiverEmail, $receiverEmail]);
        $user = $stmt->fetch();
    }
    $notificationTitle = $notificationKind === 'reminder'
        ? 'Document reminder'
        : 'New ' . ucfirst($documentType) . ' received';
    $inAppSent = $user ? createNotification($user['id'], $notificationTitle, $message, 'document', "?page={$type}") : false;

    if (filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
        $safeTitle = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
        $safeRef = htmlspecialchars((string) $refNumber, ENT_QUOTES, 'UTF-8');
        $safeReceiver = htmlspecialchars((string) $receiverName, ENT_QUOTES, 'UTF-8');
        $pageUrl = 'index.php?page=' . rawurlencode($type);
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
            $pageUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . ($basePath === '/' ? '' : $basePath) . '/index.php?page=' . rawurlencode($type);
        }
        $isReminder = $notificationKind === 'reminder';
        $heading = $isReminder ? 'Intermediary reminder' : 'New ' . ucfirst($documentType) . ' received';
        $intro = $isReminder
            ? 'The Intermediary Officer sent you the following reminder about your pending document:'
            : "A new {$documentType} has been assigned to you through NALA ERMS. Please review it and take the required action.";
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $html = "<h2>{$heading}</h2>"
            . "<p>Hello {$safeReceiver},</p>"
            . "<p>{$intro}</p>"
            . ($isReminder
                ? "<p><strong>Message:</strong> {$safeMessage}</p>"
                : "<p><strong>Title:</strong> {$safeTitle}<br><strong>Reference:</strong> {$safeRef}"
                    . ($senderName !== '' ? "<br><strong>From:</strong> " . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') : '')
                    . "<br><strong>Received:</strong> " . htmlspecialchars($receivedAt, ENT_QUOTES, 'UTF-8') . "</p>")
            . "<p><a href=\"" . htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') . "\">Sign in to view the document</a></p>"
            . "<p>This is an automatic notification from NALA ERMS.</p>";
        // Mail failure must not undo document creation or the in-app alert.
        $subject = ($isReminder ? 'Intermediary reminder - ' : 'New document received - ') . $refNumber;
        send_html_email($receiverEmail, $subject, $html);
    }

    return $inAppSent;
}

function hasCreatePermission($permission) {
    return hasPermission($permission);
}

function canEditDocument($document, $type = '') {
    $userName = $_SESSION['user_name'] ?? '';
    $editableStatuses = ['draft', 'pending', 'pending_approval', 'in-process'];
    return ($document['sender_name'] ?? '') === $userName
        && in_array($document['status'] ?? '', $editableStatuses, true);
}

function getUnreadNotificationCount($userId) {
    global $pdo;
    ensureNotificationsTable();

    $userId = (int) $userId;
    if ($userId <= 0) {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getUserNotifications($userId) {
    global $pdo;
    ensureNotificationsTable();

    $userId = (int) $userId;
    if ($userId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markNotificationsRead($userId) {
    global $pdo;
    ensureNotificationsTable();

    $userId = (int) $userId;
    if ($userId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$userId]);
}

// User management
function createUser($data) {
    global $pdo;
    $firstName = trim($data['first_name'] ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $fullName = trim($data['full_name'] ?? '');

    if ($fullName === '') {
        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, first_name, middle_name, last_name, email, password_hash, role, department, is_active) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    return $stmt->execute([
        $data['username'],
        $fullName,
        $firstName,
        $middleName,
        $lastName,
        $data['email'],
        $password_hash,
        $data['role'],
        $data['department'],
        1
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

function resetUserPassword($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ? AND role != 'admin'");
    return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $username]);
}

function setUserActive($username, $active) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE username = ? AND role != 'admin'");
    return $stmt->execute([(int) $active, $username]);
}

function deleteUser($username) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE username = ? AND role != 'admin'");
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
    $query = trim($criteria['query'] ?? '');
    if ($query === '') {
        return $results;
    }

    $searchTerm = '%' . $query . '%';
    $field = $criteria['field'] ?? 'all';

    // Keep the searchable columns explicit. This prevents a request parameter
    // from being used as a SQL column name.
    $incomingFields = [
        'reference_number' => ['ref_number'],
        'sender_unique_number' => ['sender_unique_num'],
        'sender_name' => ['sender_name'],
        'receiver_name' => ['receiver_name'],
        'keywords' => ['keywords'],
        'title' => ['title'],
        'description' => ['description']
    ];
    $standardFields = [
        'reference_number' => ['ref_number'],
        'sender_name' => ['sender_name'],
        'receiver_name' => ['receiver_name'],
        'sender_unique_number' => ['sender_unique_num'],
        'title' => ['title'],
        'description' => ['description']
    ];

    $buildSearch = function ($table, $docType, $fields) use ($pdo, $field, $searchTerm) {
        if ($field === 'all') {
            $columns = [];
            foreach ($fields as $fieldColumns) {
                $columns = array_merge($columns, $fieldColumns);
            }
            $columns = array_values(array_unique($columns));
        } elseif (isset($fields[$field])) {
            $columns = $fields[$field];
        } else {
            return [];
        }

        $where = implode(' OR ', array_map(function ($column) {
            return "$column LIKE ?";
        }, $columns));
        $sql = "SELECT *, ? AS doc_type FROM $table WHERE ($where) ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$docType], array_fill(0, count($columns), $searchTerm)));
        return $stmt->fetchAll();
    };
    
    // Search incoming
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'incoming') {
        $results = array_merge($results, $buildSearch('incoming_letters', 'Incoming Letter', $incomingFields));
    }
    
    // Search outgoing
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'outgoing') {
        $results = array_merge($results, $buildSearch('outgoing_letters', 'Outgoing Letter', $standardFields));
    }
    
    // Search internal
    if (empty($criteria['type']) || $criteria['type'] === 'all' || $criteria['type'] === 'internal') {
        $results = array_merge($results, $buildSearch('internal_memos', 'Internal Memo', $standardFields));
    }
    
    return array_values(array_filter($results, static function ($document) {
        $type = ($document['doc_type'] ?? '') === 'Incoming Letter' ? 'incoming'
            : (($document['doc_type'] ?? '') === 'Outgoing Letter' ? 'outgoing' : 'internal');
        return canViewDocument($document, $type);
    }));
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
