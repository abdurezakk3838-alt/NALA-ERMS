<?php
require_once '../includes/functions.php'; requireLogin();
if (!hasPermission('workflow') || $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php?page=workflow'); exit; }
ensureWorkflowTable();
$stmt = $pdo->prepare('UPDATE workflow_rules SET is_active = NOT is_active WHERE id = ?'); $stmt->execute([(int) ($_POST['id'] ?? 0)]);
header('Location: ../index.php?page=workflow'); exit;
