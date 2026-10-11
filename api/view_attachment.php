<?php
require_once '../includes/functions.php';
requireLogin();

$ref = $_GET['ref'] ?? '';
$type = $_GET['type'] ?? 'incoming';

if (empty($ref)) {
    http_response_code(400);
    echo 'Reference number is required.';
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
}

if (!$letter) {
    http_response_code(404);
    echo 'Letter not found.';
    exit;
}

if (!canViewDocument($letter, $type)) {
    http_response_code(403);
    echo 'You do not have permission to view this attachment.';
    exit;
}

$fileName = $letter['file_name'] ?? 'attachment';
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$fileData = $letter['file_data'];

// Older uploads may have an empty database blob because the temporary file
// was read after it had already been moved. Use the stored upload as fallback.
if (empty($fileData)) {
    $storedFile = __DIR__ . '/../uploads/' . basename($fileName);
    if (!is_file($storedFile) || !is_readable($storedFile)) {
        http_response_code(404);
        echo 'Attachment not found.';
        exit;
    }
    $binary = file_get_contents($storedFile);
} else {
    $decoded = base64_decode($fileData, true);
    $binary = $decoded !== false ? $decoded : $fileData;
}

$mimeTypes = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'txt' => 'text/plain; charset=utf-8',
    'html' => 'text/html; charset=utf-8',
    'csv' => 'text/csv; charset=utf-8',
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
echo $binary;
exit;
