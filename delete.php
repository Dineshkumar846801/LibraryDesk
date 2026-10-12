<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid security token. Go back and refresh the page before trying again.');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id && $id > 0) {
    $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: index.php?status=deleted');
exit;
