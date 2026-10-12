<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
$stmt->execute([$id]);
$book = $stmt->fetch();
if (!$book) { http_response_code(404); exit('Book not found. <a href="index.php">Return to collection</a>'); }

$errors = [];
$data = ['title' => $book['title'], 'author' => $book['author'], 'isbn' => $book['isbn'] ?? '', 'category' => $book['category'] ?? '', 'price' => (string)$book['price'], 'quantity' => (string)$book['quantity']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Your session token is invalid. Refresh the page and try again.';
    foreach ($data as $key => $value) $data[$key] = trim((string)($_POST[$key] ?? ''));
    if ($data['title'] === '' || mb_strlen($data['title']) > 200) $errors[] = 'Title is required and must be at most 200 characters.';
    if ($data['author'] === '' || mb_strlen($data['author']) > 150) $errors[] = 'Author is required and must be at most 150 characters.';
    if (mb_strlen($data['isbn']) > 30) $errors[] = 'ISBN must be at most 30 characters.';
    if (mb_strlen($data['category']) > 100) $errors[] = 'Category must be at most 100 characters.';
    if (!is_numeric($data['price']) || (float)$data['price'] < 0 || (float)$data['price'] > 99999999.99) $errors[] = 'Enter a valid non-negative price.';
    if (filter_var($data['quantity'], FILTER_VALIDATE_INT) === false || (int)$data['quantity'] < 0) $errors[] = 'Quantity must be a non-negative whole number.';
    if (!$errors) {
        $update = $pdo->prepare('UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, price = ?, quantity = ? WHERE id = ?');
        $update->execute([$data['title'], $data['author'], $data['isbn'] ?: null, $data['category'] ?: null, number_format((float)$data['price'], 2, '.', ''), (int)$data['quantity'], $id]);
        header('Location: index.php?status=updated');
        exit;
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit Book | Library Desk</title><link rel="stylesheet" href="style.css"></head>
<body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">B</span><span>Library<span class="brand-light">Desk</span></span></a><a class="top-link" href="index.php">← Back to collection</a></header>
<main class="form-container">
  <p class="eyebrow">BOOK CATALOG · ID #<?= (int)$id ?></p><h1>Edit book details</h1><p class="subheading">Make your changes and save the updated record.</p>
  <?php if ($errors): ?><div class="alert error"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form class="book-form panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <div class="form-grid">
      <div class="field full"><label for="title">Book title <span>*</span></label><input id="title" name="title" maxlength="200" required value="<?= e($data['title']) ?>"></div>
      <div class="field"><label for="author">Author <span>*</span></label><input id="author" name="author" maxlength="150" required value="<?= e($data['author']) ?>"></div>
      <div class="field"><label for="isbn">ISBN</label><input id="isbn" name="isbn" maxlength="30" value="<?= e($data['isbn']) ?>"></div>
      <div class="field"><label for="category">Category</label><input id="category" name="category" maxlength="100" value="<?= e($data['category']) ?>"></div>
      <div class="field"><label for="price">Price (₹) <span>*</span></label><input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?= e($data['price']) ?>"></div>
      <div class="field"><label for="quantity">Quantity <span>*</span></label><input id="quantity" name="quantity" type="number" min="0" step="1" required value="<?= e($data['quantity']) ?>"></div>
    </div>
    <div class="form-actions"><a class="button button-outline" href="index.php">Cancel</a><button class="button button-primary" type="submit">Update Book</button></div>
  </form>
  <p class="form-hint"><span>*</span> Required fields</p>
</main>
</body></html>
