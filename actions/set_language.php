<?php
require_once '../includes/functions.php';
requireLogin();

$language = $_POST['language'] ?? '';
if (!in_array($language, ['en', 'am', 'om'], true)) {
    jsonResponse(['success' => false, 'message' => 'Unsupported language.'], 400);
}

$_SESSION['language'] = $language;
jsonResponse(['success' => true, 'language' => $language]);
