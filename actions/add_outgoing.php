<?php
// actions/add_outgoing.php
require_once '../includes/functions.php';
requireLogin();

// Prevent roles that are not allowed to create outgoing letters (e.g., archivist)
if (!hasCreatePermission('outgoing')) {
    $_SESSION['flash_message'] = 'Your account is not permitted to create outgoing letters.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ../index.php?page=outgoing');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_number = $_POST['ref_number'] ?? generateOutRefNumber();
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    // Ownership is always the authenticated sender; do not trust a posted name.
    $sender_name = $_SESSION['user_name'];
    $receiver_name = $_POST['receiver_name'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? '';
    $classification = $_POST['classification'] ?? '1.01 General Administration';
    $priority = $_POST['priority'] ?? 'Normal';
    $security_level = $_POST['security_level'] ?? 'Official';
    $keywords = $_POST['keywords'] ?? null;
    $sender_unique_num = trim($_POST['sender_unique_num'] ?? '');
    $receiver_organization = $_POST['receiver_organization'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $edit_ref = $_POST['edit_out_ref'] ?? '';

    // Handle file upload
    $file_name = null;
    $file_data = null;
    if (isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] === UPLOAD_ERR_OK) {
        $raw_file_data = file_get_contents($_FILES['attached_file']['tmp_name']);
        $uploaded_file = uploadFile($_FILES['attached_file']);
        if ($uploaded_file) {
            $file_name = $uploaded_file;
            $file_data = base64_encode($raw_file_data);
        }
    }

    if ($edit_ref) {
        $existingLetter = getOutgoingLetter($edit_ref);
        if (!$existingLetter || !canEditDocument($existingLetter, 'outgoing')) {
            $_SESSION['flash_message'] = 'Only the sender can edit this letter before it is approved or rejected.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ../index.php?page=outgoing');
            exit;
        }
        // Update existing outgoing letter
        $data = [
            'title' => $title,
            'description' => $description,
            'sender_name' => $existingLetter['sender_name'],
            'sender_unique_num' => $existingLetter['sender_unique_num'] ?? $sender_unique_num,
            'keywords' => $existingLetter['keywords'] ?? $keywords,
            'receiver_organization' => $existingLetter['receiver_organization'] ?? $receiver_organization,
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'classification' => $_POST['classification'] ?? ($existingLetter['classification'] ?? $classification),
            'priority' => $_POST['priority'] ?? ($existingLetter['priority'] ?? $priority),
            'security_level' => $security_level,
            'date' => $date,
        ];
        if ($file_name) {
            $data['file_name'] = $file_name;
            $data['file_data'] = $file_data;
        }
        $result = updateOutgoingLetter($edit_ref, $data);
        if ($result) {
            logAudit($_SESSION['user_name'], 'Outgoing Letter Updated', "Ref: $edit_ref | Edited by: {$_SESSION['user_name']}");
            $message = 'Outgoing letter updated successfully.';
        } else {
            $message = 'Outgoing letter could not be updated.';
        }
    } else {
        // Create new outgoing letter
        $data = [
            'ref_number' => $ref_number,
            'title' => $title,
            'description' => $description,
            'sender_name' => $sender_name,
            'sender_unique_num' => $sender_unique_num,
            'keywords' => $keywords,
            'receiver_organization' => $receiver_organization,
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'classification' => $classification,
            'priority' => $priority,
            'security_level' => $security_level,
            'date' => $date,
            'status' => 'pending_approval',
            'workflow_stage' => 'head_review',
            'file_name' => $file_name,
            'file_data' => $file_data,
        ];
        $result = createOutgoingLetter($data);
        if ($result) {
            $reviewMessage = "Outgoing letter \"{$title}\" (Ref: {$ref_number}) from {$sender_name} was submitted on " . date('d M Y, H:i') . ". Please review it and forward it for final approval or return it for correction.";
            notifyUsersByRole(['head_office'], 'Outgoing letter awaiting your review', $reviewMessage, 'workflow', '?page=outgoing');
            notifyReceiverAboutDocument($receiver_name, $title, $ref_number, 'outgoing', $receiver_email, 'document', $sender_name);
        }
        logAudit($_SESSION['user_name'], 'Outgoing Letter Created', "Ref: $ref_number");
        $message = 'Outgoing letter created and submitted for approval.';
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $result ? 'success' : 'error';

    header('Location: ../index.php?page=outgoing');
    exit;
} else {
    header('Location: ../index.php?page=outgoing');
    exit;
}
