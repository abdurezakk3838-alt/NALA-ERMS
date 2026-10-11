<?php
// actions/add_incoming.php
require_once '../includes/functions.php';
requireLogin();
ensureIncomingSchema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_number = trim($_POST['ref_number'] ?? '');
    if ($ref_number === '') {
        $ref_number = generateRefNumber();
    }
    $sender_unique_num = $_POST['sender_unique_num'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $sender_name = $_POST['sender_name'] ?? '';
    $receiver_name = $_POST['receiver_name'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? '';
    $received_date = $_POST['received_date'] ?? date('Y-m-d');
    $received_time = $_POST['received_time'] ?? date('H:i');
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $received_time)) {
        $_SESSION['flash_message'] = 'Please enter a valid received time.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ../index.php?page=incoming');
        exit;
    }
    $classification = $_POST['classification'] ?? '1.01 General Administration';
    $security_level = $_POST['security_level'] ?? 'Official';
    $keywords = $_POST['keywords'] ?? '';
    $edit_ref = $_POST['edit_ref_number'] ?? '';
    
    // Handle file upload
    $file_name = null;
    $file_data = null;
    if (isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] === UPLOAD_ERR_OK) {
        // Read the temporary upload before uploadFile() moves it.
        $raw_file_data = file_get_contents($_FILES['attached_file']['tmp_name']);
        $file_data = $raw_file_data === false ? null : base64_encode($raw_file_data);
        $uploaded_file = uploadFile($_FILES['attached_file']);
        if ($uploaded_file) {
            $file_name = $uploaded_file;
        } else {
            $file_data = null;
        }
    }
    
    if ($edit_ref) {
        // Update existing letter
        $existingLetter = getIncomingLetter($edit_ref);
        if (!$existingLetter || !canEditDocument($existingLetter, 'incoming')) {
            $_SESSION['flash_message'] = 'Only the sender can edit this letter before it is approved or rejected.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ../index.php?page=incoming');
            exit;
        }
        $data = [
            'sender_unique_num' => $sender_unique_num,
            'title' => $title,
            'description' => $description,
            'sender_name' => $sender_name,
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'received_date' => $received_date,
            'received_time' => $received_time,
            'classification' => $classification,
            'security_level' => $security_level,
            'keywords' => $keywords,
        ];
        if ($file_name) {
            $data['file_name'] = $file_name;
            $data['file_data'] = $file_data;
        }
        $result = updateIncomingLetter($edit_ref, $data);
        if ($result) logAudit($_SESSION['user_name'], 'Incoming Letter Updated', "Ref: $edit_ref");
        $message = $result ? 'Letter updated successfully.' : 'Letter could not be updated.';
    } else {
        // Create new letter
        $data = [
            'ref_number' => $ref_number,
            'sender_unique_num' => $sender_unique_num,
            'title' => $title,
            'description' => $description,
            'sender_name' => $sender_name,
            'receiver_name' => $receiver_name,
            'receiver_email' => $receiver_email,
            'received_date' => $received_date,
            'received_time' => $received_time,
            'classification' => $classification,
            'security_level' => $security_level,
            'keywords' => $keywords,
            'status' => 'pending',
            'file_name' => $file_name,
            'file_data' => $file_data,
        ];
        try {
            $result = createIncomingLetter($data);
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo ?? [];
            $errorMessage = strtolower($errorInfo[2] ?? '');
            if ($e->getCode() !== '23000' || (int) ($errorInfo[1] ?? 0) !== 1062 || strpos($errorMessage, 'ref_number') === false) {
                throw $e;
            }
            $_SESSION['flash_message'] = 'This reference number is already in use. Please enter a different reference number.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ../index.php?page=incoming');
            exit;
        }
        if ($result) notifyReceiverAboutDocument($receiver_name, $title, $ref_number, 'incoming', $receiver_email, 'document', $sender_name);
        logAudit($_SESSION['user_name'], 'Incoming Letter Created', "Ref: $ref_number");
        $message = 'Letter registered successfully.';
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $result ? 'success' : 'error';
    
    header('Location: ../index.php?page=incoming');
    exit;
} else {
    header('Location: ../index.php?page=incoming');
    exit;
}
?>
