<?php
require_once '../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);

$ref = trim($_POST['ref'] ?? '');
$method = $_POST['method'] ?? 'email';
$recipient = trim($_POST['recipient'] ?? '');
$dispatchReference = trim($_POST['dispatch_reference'] ?? '');
$letter = $ref ? getOutgoingLetter($ref) : null;

if (!$letter || !canDispatchOutgoingLetter($letter)) {
    jsonResponse(['success' => false, 'message' => 'Only the Records Office can dispatch a registered outgoing letter.'], 403);
}
if (!in_array($method, ['email', 'physical'], true)) {
    jsonResponse(['success' => false, 'message' => 'Choose email or physical dispatch.'], 422);
}
if ($method === 'email') {
    $recipient = $recipient !== '' ? $recipient : ($letter['receiver_email'] ?? '');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'A valid recipient email is required.'], 422);
    }
} elseif ($recipient === '') {
    jsonResponse(['success' => false, 'message' => 'Enter the courier, postal, or hand-delivery details.'], 422);
}

// Send the registered signed/stamped record by email when the server mail
// transport is configured. The archival copy always stores the signature,
// stamp, attachment, and delivery metadata regardless of mail configuration.
$mailSent = null;
if ($method === 'email' && function_exists('mail')) {
    $subject = 'NALA ERMS: Digitally signed outgoing letter ' . ($letter['registration_reference'] ?: $ref);
    $body = "Dear recipient,\n\nPlease find attached the digitally signed and officially stamped outgoing letter " . ($letter['registration_reference'] ?: $ref) . ".\n\nRegards,\nNALA ERMS Records Office";
    $headers = "From: NALA ERMS <no-reply@localhost>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if (!empty($letter['file_name']) && !empty($letter['file_data'])) {
        $boundary = md5((string) microtime(true));
        $headers = "From: NALA ERMS <no-reply@localhost>\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$body}\r\n\r\n";
        $body .= "--{$boundary}\r\nContent-Type: application/octet-stream; name=\"" . basename($letter['file_name']) . "\"\r\nContent-Disposition: attachment; filename=\"" . basename($letter['file_name']) . "\"\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split($letter['file_data']) . "\r\n--{$boundary}--";
    }
    $mailSent = @mail($recipient, $subject, $body, $headers);
}

$result = updateOutgoingLetter($ref, [
    'status' => 'dispatched',
    'workflow_stage' => 'archived',
    'dispatch_method' => $method,
    'dispatch_recipient' => $recipient,
    'dispatch_reference' => $dispatchReference,
    'dispatched_by' => $_SESSION['user_name'],
    'dispatched_at' => date('Y-m-d H:i:s')
]);
if (!$result) jsonResponse(['success' => false, 'message' => 'The letter could not be marked as dispatched.'], 500);

recordOutgoingWorkflow($ref, 'archived', 'Dispatched and archived', "Method: {$method}; Recipient: {$recipient}; Delivery reference: {$dispatchReference}");
$updatedLetter = getOutgoingLetter($ref);
if (!$updatedLetter) {
    jsonResponse(['success' => false, 'message' => 'Dispatch was recorded but the final letter could not be loaded for archiving.'], 500);
}
$updatedLetter['workflow_history'] = getOutgoingWorkflowHistory($ref);
if (!archiveDocument('outgoing_letters', $updatedLetter)) {
    jsonResponse(['success' => false, 'message' => 'Dispatch was recorded but final archiving failed. Contact the administrator.'], 500);
}
notifyArchivistsAboutDocument($updatedLetter, 'outgoing', 'dispatched');
logAudit($_SESSION['user_name'], 'Outgoing Letter Dispatched and Archived', "Ref: {$ref} | Method: {$method}");

$message = $method === 'email'
    ? ($mailSent === false ? 'Dispatch recorded and archived, but the server email transport did not send the message.' : 'Digitally signed letter emailed and archived successfully.')
    : 'Letter marked for physical delivery and archived successfully.';
jsonResponse(['success' => true, 'message' => $message, 'mail_sent' => $mailSent]);
