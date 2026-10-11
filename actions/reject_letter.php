<?php
// actions/reject_letter.php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$ref = $_POST['ref'] ?? '';
$type = $_POST['type'] ?? 'incoming';
$remark = $_POST['remark'] ?? '';

if (empty($ref)) {
    echo json_encode(['success' => false, 'message' => 'Reference is required']);
    exit;
}

if (empty($remark)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

$result = false;
$sourceTable = '';
$letter = null;

if (!in_array($type, ['incoming', 'outgoing', 'internal'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid document type']);
    exit;
}

ensureArchiveTable();
ensureNotificationsTable();
if ($type === 'incoming') {
    ensureIncomingSchema();
}
if ($type === 'outgoing') {
    ensureOutgoingWorkflowColumns();
    ensureOutgoingWorkflowHistoryTable();
}

$pdo->beginTransaction();

switch ($type) {
    case 'incoming':
        $letter = getIncomingLetter($ref);
        if ($letter && canReviewDocument($letter, $type)) {
            $sourceTable = 'incoming_letters';
            $data = [
                'status' => 'rejected',
                'receiver_remark' => $remark,
                'digital_signature' => '',
                'signed_by' => null,
                'signed_date' => null
            ];
            $result = updateIncomingLetter($ref, $data);
        }
        break;
        
    case 'outgoing':
        $letter = getOutgoingLetter($ref);
        if ($letter && canApproveOutgoingLetter($letter)) {
            $sourceTable = 'outgoing_letters';
            $data = [
                'status' => 'rejected',
                'manager_remark' => $remark,
                'digital_signature' => '',
                'signed_by' => null,
                'signed_date' => null
            ];
            $result = updateOutgoingLetter($ref, $data);
        }
        break;
        
    case 'internal':
        $letter = getInternalMemo($ref);
        if ($letter && canReviewDocument($letter, $type)) {
            $sourceTable = 'internal_memos';
            $data = [
                'status' => 'rejected',
                'receiver_remark' => $remark,
                'digital_signature' => '',
                'signed_by' => null,
                'signed_date' => null
            ];
            $result = updateInternalMemo($ref, $data);
        }
        break;
        
}

if (!$result) {
    $pdo->rollBack();
    // Log denied rejection attempts for diagnostics
    $details = "Reject attempt failed: Ref={$ref} | User={$_SESSION['user_name']} | Role={$_SESSION['user_role']} | LetterStatus=" . ($letter['status'] ?? 'none') . " | Receiver=" . ($letter['receiver_name'] ?? '');
    logAudit($_SESSION['user_name'], 'Reject attempt denied', $details);

    if ($letter && ($letter['status'] ?? '') === 'rejected') {
        echo json_encode([
            'success' => true,
            'already_processed' => true,
            'message' => 'This document has already been rejected. No duplicate rejection was made.'
        ]);
        exit;
    }
    if ($letter && ($letter['status'] ?? '') === 'approved') {
        echo json_encode([
            'success' => false,
            'message' => 'This document has already been approved and cannot be rejected.'
        ]);
        exit;
    }

    if ($letter) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to reject this document or it is not assigned to you.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
    }
    exit;
}

if ($type === 'outgoing') {
    recordOutgoingWorkflow($ref, 'director_approval', 'Rejected by Director General', $remark);
}

try {
    $updatedLetter = $type === 'incoming' ? getIncomingLetter($ref) : ($type === 'outgoing' ? getOutgoingLetter($ref) : getInternalMemo($ref));
    if ($type === 'outgoing' && $updatedLetter) {
        $updatedLetter['workflow_history'] = getOutgoingWorkflowHistory($ref);
    }
    if (!archiveDocument($sourceTable, $updatedLetter)) {
        throw new RuntimeException('Unable to archive the rejected document.');
    }
    notifyArchivistsAboutDocument($updatedLetter, $type, 'rejected');
    logAudit($_SESSION['user_name'], ucfirst($type) . ' Letter Rejected and Archived', "Ref: $ref | Reason: $remark");
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Document rejected and saved to the archive.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Document was rejected, but could not be archived. Please contact the records office.']);
}
