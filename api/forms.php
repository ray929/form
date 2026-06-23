<?php
/**
 * Forms CRUD API
 * GET  ?action=delete&id=X   —  delete a form
 * POST ?action=create        —  create a form (multipart/form-data)
 * POST ?action=update        —  update a form (multipart/form-data)
 */

$action = $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'error' => 'Invalid form ID'], 400);
    }
    App::db()->prepare("DELETE FROM forms WHERE id = ?")->execute([$id]);
    json_response(['success' => true]);
}

if ($action === 'create') {
    require_post();
    $name = trim($_POST['name'] ?? '');
    $recipientEmail = trim($_POST['recipient_email'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $queryPassword = trim($_POST['query_password'] ?? '');
    $queryPassHash = $queryPassword !== '' ? password_hash($queryPassword, PASSWORD_BCRYPT) : '';

    if ($name === '') {
        json_response(['success' => false, 'error' => 'Form name is required.'], 400);
    }

    App::db()->prepare("INSERT INTO forms (name, recipient_email, query_password, status) VALUES (?, ?, ?, ?)")
       ->execute([$name, $recipientEmail, $queryPassHash, $status]);
    json_response(['success' => true, 'id' => App::db()->lastInsertId()]);
}

if ($action === 'update') {
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        json_response(['success' => false, 'error' => 'Invalid form ID'], 400);
    }

    $stmt = App::db()->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        json_response(['success' => false, 'error' => 'Form not found'], 404);
    }

    $name = trim($_POST['name'] ?? '');
    $recipientEmail = trim($_POST['recipient_email'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $queryPassword = trim($_POST['query_password'] ?? '');
    $queryPassHash = $queryPassword !== '' ? password_hash($queryPassword, PASSWORD_BCRYPT) : $existing['query_password'];

    if ($name === '') {
        json_response(['success' => false, 'error' => 'Form name is required.'], 400);
    }

    App::db()->prepare("UPDATE forms SET name = ?, recipient_email = ?, query_password = ?, status = ?, updated_at = datetime('now') WHERE id = ?")
       ->execute([$name, $recipientEmail, $queryPassHash, $status, $id]);
    json_response(['success' => true]);
}

json_response(['success' => false, 'error' => 'Unknown action'], 400);
