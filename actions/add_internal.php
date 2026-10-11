<?php
// actions/add_internal.php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_number = $_POST['ref_number'] ?? generateInternalRefNumber();
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    // Ownership is always the authenticated sender; do not trust a posted name.
    $sender_name = $_SESSION['user_name'];
    $receiver_name = $_POST['receiver_name'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $edit_ref = $_POST['edit_internal_ref'] ?? '';

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
        $existingMemo = getInternalMemo($edit_ref);
        if (!$existingMemo || !canEditDocument($existingMemo, 'internal')) {
            $_SESSION['flash_message'] = 'Only the sender can edit this memo before it is approved or rejected.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ../index.php?page=internal');
            exit;
        }
        // Update existing internal memo
        $data = [
            'title' => $title,
            'description' => $description,
            'sender_name' => $existingMemo['sender_name'],
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'date' => $date,
        ];
        if ($file_name) {
            $data['file_name'] = $file_name;
            $data['file_data'] = $file_data;
        }
        $result = updateInternalMemo($edit_ref, $data);
        if ($result) {
            logAudit($_SESSION['user_name'], 'Internal Memo Updated', "Ref: $edit_ref | Edited by: {$_SESSION['user_name']}");
            $message = 'Internal memo updated successfully.';
        } else {
            $message = 'Internal memo could not be updated.';
        }
    } else {
        // Create new internal memo
        $data = [
            'ref_number' => $ref_number,
            'title' => $title,
            'description' => $description,
            'sender_name' => $sender_name,
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'date' => $date,
            'status' => 'pending',
            'file_name' => $file_name,
            'file_data' => $file_data,
        ];
        $result = createInternalMemo($data);
        if ($result) notifyReceiverAboutDocument($receiver_name, $title, $ref_number, 'internal', $receiver_email, 'document', $sender_name);
        logAudit($_SESSION['user_name'], 'Internal Memo Created', "Ref: $ref_number");
        $message = 'Internal memo sent successfully.';
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $result ? 'success' : 'error';

    header('Location: ../index.php?page=internal');
    exit;
} else {
    header('Location: ../index.php?page=internal');
    exit;
}
