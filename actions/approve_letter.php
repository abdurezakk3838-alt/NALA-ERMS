<?php
// actions/approve_letter.php
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
$signature = $_POST['signature'] ?? '';

if (empty($ref)) {
    echo json_encode(['success' => false, 'message' => 'Reference is required']);
    exit;
}

if ($signature !== '' && strpos($signature, 'data:image/') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid digital signature']);
    exit;
}
$signature = $signature ?: generateSystemSignature($ref, $_SESSION['user_name'], date('Y-m-d H:i:s'));

$result = false;
$sourceTable = '';
$letter = null;
$deferOutgoingArchive = false;

if (!in_array($type, ['incoming', 'outgoing', 'internal'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid document type']);
    exit;
}

ensureArchiveTable();
if ($type === 'incoming') {
    ensureIncomingSchema();
}
ensureNotificationsTable();
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
                'status' => 'approved',
                'receiver_remark' => $remark,
                'digital_signature' => $signature,
                'signed_by' => $_SESSION['user_name'],
                'signed_date' => date('Y-m-d H:i:s')
            ];
            $result = updateIncomingLetter($ref, $data);
        }
        break;
        
    case 'outgoing':
        $letter = getOutgoingLetter($ref);
        if ($letter && canApproveOutgoingLetter($letter)) {
            $sourceTable = 'outgoing_letters';
            $signedAt = date('Y-m-d H:i:s');
            $data = [
                'status' => 'approved',
                'workflow_stage' => 'records_registration',
                'manager_remark' => $remark,
                'digital_signature' => $signature,
                'digital_stamp' => generateOfficialDigitalStamp($ref, $_SESSION['user_name'], $signedAt),
                'signed_by' => $_SESSION['user_name'],
                'signed_date' => $signedAt
            ];
            $result = updateOutgoingLetter($ref, $data);
            $deferOutgoingArchive = true;
        }
        break;
        
    case 'internal':
        $letter = getInternalMemo($ref);
        if ($letter && canReviewDocument($letter, $type)) {
            $sourceTable = 'internal_memos';
            $data = [
                'status' => 'approved',
                'receiver_remark' => $remark,
                'digital_signature' => $signature,
                'signed_by' => $_SESSION['user_name'],
                'signed_date' => date('Y-m-d H:i:s')
            ];
            $result = updateInternalMemo($ref, $data);
        }
        break;
        
}

if (!$result) {
    $pdo->rollBack();
    // Log denied approval attempts for diagnostics
    $details = "Approve attempt failed: Ref={$ref} | User={$_SESSION['user_name']} | Role={$_SESSION['user_role']} | LetterStatus=" . ($letter['status'] ?? 'none') . " | Receiver=" . ($letter['receiver_name'] ?? '');
    logAudit($_SESSION['user_name'], 'Approve attempt denied', $details);

    if ($letter && ($letter['status'] ?? '') === 'approved') {
        echo json_encode([
            'success' => true,
            'already_processed' => true,
            'message' => 'This document has already been approved. No duplicate approval was made.'
        ]);
        exit;
    }
    if ($letter && ($letter['status'] ?? '') === 'rejected') {
        echo json_encode([
            'success' => false,
            'message' => 'This document has already been rejected and cannot be approved.'
        ]);
        exit;
    }

    // More descriptive error to help users understand why approval failed
    if ($letter) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to approve this document or it is not assigned to you.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
    }
    exit;
}

if ($deferOutgoingArchive) {
    recordOutgoingWorkflow($ref, 'records_registration', 'Approved, digitally signed, and officially stamped by Director General', $remark);
    $updatedLetter = getOutgoingLetter($ref);
    if (!$updatedLetter) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Letter was approved but could not be loaded for the Archive.']);
        exit;
    }
    $updatedLetter['workflow_history'] = getOutgoingWorkflowHistory($ref);
    if (!archiveDocument($sourceTable, $updatedLetter)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Letter was approved but could not be saved to the Archive.']);
        exit;
    }
    notifyArchivistsAboutDocument($updatedLetter, $type, 'approved');
    notifyUsersByRole(['records_officer'], 'Outgoing letter ready for registration', "Outgoing letter {$ref} was digitally approved and stamped. Register it in the Records Office.");
    $senderStmt = $pdo->prepare('SELECT id FROM users WHERE full_name = ? AND is_active != 0 LIMIT 1');
    $senderStmt->execute([$letter['sender_name']]);
    if ($senderId = $senderStmt->fetchColumn()) {
        notifyUser($senderId, 'Outgoing letter approved', "Your outgoing letter {$ref} was digitally approved and stamped by Director General {$_SESSION['user_name']}.", 'outgoing_workflow', 'index.php?page=outgoing');
    }
    logAudit($_SESSION['user_name'], 'Outgoing Letter Digitally Approved and Stamped', "Ref: {$ref}");
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Letter approved, digitally signed, stamped, and sent to the Records Office.']);
    exit;
}

try {
    $updatedLetter = $type === 'incoming' ? getIncomingLetter($ref) : ($type === 'outgoing' ? getOutgoingLetter($ref) : getInternalMemo($ref));
    if (!archiveDocument($sourceTable, $updatedLetter)) {
        throw new RuntimeException('Unable to archive the approved document.');
    }
    notifyArchivistsAboutDocument($updatedLetter, $type, 'approved');
    logAudit($_SESSION['user_name'], ucfirst($type) . ' Letter Approved and Archived', "Ref: $ref");
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Document approved and saved to the archive.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Document was approved, but could not be archived. Please contact the records office.']);
}
