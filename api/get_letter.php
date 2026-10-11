<?php
// api/get_letter.php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$ref = $_GET['ref'] ?? '';
$type = $_GET['type'] ?? 'incoming';

if (empty($ref)) {
    echo json_encode(['success' => false, 'message' => 'Reference number is required']);
    exit;
}

$letter = null;

switch ($type) {
    case 'incoming':
        $letter = getIncomingLetter($ref);
        break;
    case 'outgoing':
        $letter = getOutgoingLetter($ref);
        break;
    case 'internal':
        $letter = getInternalMemo($ref);
        break;
    default:
        // Try all types
        $letter = getIncomingLetter($ref);
        if (!$letter) $letter = getOutgoingLetter($ref);
        if (!$letter) $letter = getInternalMemo($ref);
        break;
}

if ($letter) {
    if (!canViewDocument($letter, $type)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to view this letter.']);
        exit;
    }
    echo json_encode(['success' => true, 'letter' => $letter]);
} else {
    echo json_encode(['success' => false, 'message' => 'Letter not found']);
}
