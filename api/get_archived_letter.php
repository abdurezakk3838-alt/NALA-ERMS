<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'Archive record is required.']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM archive_records WHERE id = ?');
$stmt->execute([$id]);
$archive = $stmt->fetch();
$letter = $archive ? json_decode($archive['archived_data'] ?? '', true) : null;
$types = ['incoming_letters' => 'incoming', 'outgoing_letters' => 'outgoing', 'internal_memos' => 'internal'];

if (!$archive || !is_array($letter) || !isset($types[$archive['original_table']])) {
    echo json_encode(['success' => false, 'message' => 'Archived document not found.']);
    exit;
}

if (!canViewDocument($letter, $archive['original_table'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not allowed to view this document.']);
    exit;
}

echo json_encode(['success' => true, 'letter' => $letter, 'type' => $types[$archive['original_table']]]);
