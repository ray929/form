<?php
/**
 * Embed config API — returns form config for AJAX-based embedding
 */
$formId = (int)($_GET['form_id'] ?? 0);
if (!$formId) {
    json_response(['success' => false, 'error' => 'Invalid form ID'], 400);
}

$stmt = App::db()->prepare("SELECT id, name, recipient_email FROM forms WHERE id = ? AND status = 1");
$stmt->execute([$formId]);
$form = $stmt->fetch();

if (!$form) {
    json_response(['success' => false, 'error' => 'Form not found'], 404);
}

json_response(['success' => true, 'form' => $form]);
