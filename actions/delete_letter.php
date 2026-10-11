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
    echo json_encode(['success' => false, 'message' => 'Only the System Administrator can delete letters.']);
    exit;
}

$ref = trim($_POST['ref'] ?? '');
$type = $_POST['type'] ?? '';
$sources = [
    'incoming' => ['table' => 'incoming_letters', 'getter' => 'getIncomingLetter'],
    'outgoing' => ['table' => 'outgoing_letters', 'getter' => 'getOutgoingLetter'],
    'internal' => ['table' => 'internal_memos', 'getter' => 'getInternalMemo']
];

if ($ref === '' || !isset($sources[$type])) {
    echo json_encode(['success' => false, 'message' => 'A valid document reference and type are required.']);
    exit;
}

$letter = call_user_func($sources[$type]['getter'], $ref);
if (!$letter) {
    echo json_encode(['success' => false, 'message' => 'Letter not found.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('DELETE FROM ' . $sources[$type]['table'] . ' WHERE ref_number = ?');
    $stmt->execute([$ref]);
    logAudit($_SESSION['user_name'], 'Letter Deleted', ucfirst($type) . " letter deleted: $ref");
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Letter deleted successfully. Any existing archive copy has been retained.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Unable to delete the letter.']);
}
