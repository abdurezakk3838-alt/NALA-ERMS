<?php
// api/search.php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
$type = $_GET['type'] ?? 'all';
$field = $_GET['field'] ?? 'all';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit;
}

$results = searchRecords(['query' => $query, 'type' => $type, 'field' => $field]);
echo json_encode(['success' => true, 'results' => $results]);
