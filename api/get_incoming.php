<?php
// api/get_incoming.php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['receiver'])) $filters['receiver'] = $_GET['receiver'];
if (!empty($_GET['sender'])) $filters['sender'] = $_GET['sender'];
if (!empty($_GET['ref'])) $filters['ref'] = $_GET['ref'];

if (!empty($_GET['ref'])) {
    $letter = getIncomingLetter($_GET['ref']);
    if (!$letter) {
        echo json_encode(['success' => false, 'message' => 'Letter not found or you do not have permission to edit it.']);
        exit;
    }
    echo json_encode(['success' => true, 'letter' => $letter]);
    exit;
}

$letters = getIncomingLetters($filters);
echo json_encode(['success' => true, 'letters' => $letters]);
