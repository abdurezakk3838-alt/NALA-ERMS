<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the System Administrator can delete archived copies.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'A valid archive record is required.']);
    exit;
}

try {
    ensureArchiveTable();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT ref_number, original_table FROM archive_records WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Archived document not found.']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM archive_records WHERE id = ?');
    $stmt->execute([$id]);
    logAudit($_SESSION['user_name'], 'Archived Copy Deleted', "Archive record deleted: {$record['ref_number']} ({$record['original_table']}); original document unchanged.");
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => "Archived copy {$record['ref_number']} was deleted. The original document was not changed."]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Unable to delete the archived copy.']);
}
