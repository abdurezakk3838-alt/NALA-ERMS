<?php
require_once '../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
$ref = trim($_POST['ref'] ?? '');
$letter = $ref ? getOutgoingLetter($ref) : null;
if (!$letter || !canRegisterOutgoingLetter($letter)) {
    jsonResponse(['success' => false, 'message' => 'Only the Records Office can register a digitally approved outgoing letter.'], 403);
}

$registrationReference = trim($_POST['registration_reference'] ?? '');
if ($registrationReference === '') $registrationReference = generateOutgoingRegistrationReference();
$result = updateOutgoingLetter($ref, [
    'workflow_stage' => 'ready_for_dispatch',
    'registration_reference' => $registrationReference,
    'registered_by' => $_SESSION['user_name'],
    'registered_at' => date('Y-m-d H:i:s')
]);
if (!$result) jsonResponse(['success' => false, 'message' => 'The outgoing letter could not be registered.'], 500);

recordOutgoingWorkflow($ref, 'ready_for_dispatch', 'Registered by Records Office', "Official registration reference: {$registrationReference}");
notifyUsersByRole(['records_officer'], 'Outgoing letter registered', "Outgoing letter {$ref} is registered as {$registrationReference} and ready for dispatch.");
logAudit($_SESSION['user_name'], 'Outgoing Letter Registered', "Ref: {$ref} | Registration: {$registrationReference}");
jsonResponse(['success' => true, 'message' => "Letter registered successfully as {$registrationReference}.", 'registration_reference' => $registrationReference]);
