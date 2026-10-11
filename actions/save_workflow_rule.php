<?php
require_once '../includes/functions.php'; requireLogin();
if (!hasPermission('workflow') || $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php?page=workflow'); exit; }
ensureWorkflowTable();
$stmt = $pdo->prepare('INSERT INTO workflow_rules (name, description, trigger_event, action_type) VALUES (?, ?, ?, ?)');
$stmt->execute([trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''), $_POST['trigger_event'] ?? 'letter_submitted', $_POST['action_type'] ?? 'notify_receiver']);
header('Location: ../index.php?page=workflow'); exit;
