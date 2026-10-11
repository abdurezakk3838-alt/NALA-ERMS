<?php
require_once '../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$ref = trim($_POST['ref'] ?? '');
$remark = trim($_POST['remark'] ?? '');
$letter = $ref ? getOutgoingLetter($ref) : null;
if (!$letter || !canForwardOutgoingLetter($letter)) {
    jsonResponse(['success' => false, 'message' => 'Only a Head Officer can forward a letter at this stage.'], 403);
}

$result = updateOutgoingLetter($ref, [
    'workflow_stage' => 'director_approval',
    'head_reviewed_by' => $_SESSION['user_name'],
    'head_reviewed_at' => date('Y-m-d H:i:s'),
    'head_review_remark' => $remark
]);
if (!$result) jsonResponse(['success' => false, 'message' => 'The letter could not be forwarded.'], 500);

recordOutgoingWorkflow($ref, 'director_approval', 'Forwarded to Director General for final approval', $remark);
$reviewMessage = "Outgoing letter \"{$letter['title']}\" (Ref: {$ref}) from {$letter['sender_name']} was forwarded by Head Officer {$_SESSION['user_name']} on " . date('d M Y, H:i') . ". Please review and provide final approval or rejection.";
notifyUsersByRole(['director'], 'Outgoing letter awaiting your final review', $reviewMessage, 'workflow', '?page=outgoing');
logAudit($_SESSION['user_name'], 'Outgoing Letter Forwarded to Director', "Ref: {$ref}");
jsonResponse(['success' => true, 'message' => 'Letter forwarded to the Director General for final approval.']);
