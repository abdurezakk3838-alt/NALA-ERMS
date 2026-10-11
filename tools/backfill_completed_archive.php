<?php
// One-time migration for documents completed before automatic archiving was introduced.
require_once __DIR__ . '/../includes/functions.php';

$_SESSION['user_name'] = 'System Archive Migration';
$sources = ['incoming_letters', 'outgoing_letters', 'internal_memos'];
$archived = 0;

foreach ($sources as $source) {
    $stmt = $pdo->query("SELECT * FROM $source WHERE status IN ('approved', 'rejected')");
    foreach ($stmt->fetchAll() as $letter) {
        if (archiveDocument($source, $letter)) {
            $archived++;
        }
    }
}

echo "Completed archive backfill. Processed $archived document(s)." . PHP_EOL;
