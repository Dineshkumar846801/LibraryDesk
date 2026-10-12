<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$title = $author = $isbn = $category = '';
$price = '0.00';
$quantity = '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your session token is invalid. Refresh the page and try again.';
    }
    $title = trim((string)($_POST['title'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $isbn = trim((string)($_POST['isbn'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $price = trim((string)($_POST['price'] ?? '0'));
    $quantity = trim((string)($_POST['quantity'] ?? '0'));

    if ($title === '' || mb_strlen($title) > 200) $errors[] = 'Title is required and must be at most 200 characters.';
    if ($author === '' || mb_strlen($author) > 150) $errors[] = 'Author is required and must be at most 150 characters.';
    if (mb_strlen($isbn) > 30) $errors[] = 'ISBN must be at most 30 characters.';
    if (mb_strlen($category) > 100) $errors[] = 'Category must be at most 100 characters.';
    if (!is_numeric($price) || (float)$price < 0 || (float)$price > 99999999.99) $errors[] = 'Enter a valid non-negative price.';
    if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int)$quantity < 0) $errors[] = 'Quantity must be a non-negative whole number.';

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO books (title, author, isbn, category, price, quantity) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $author, $isbn ?: null, $category ?: null, number_format((float)$price, 2, '.', ''), (int)$quantity]);
        header('Location: index.php?status=added');
        exit;
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Add Book | Library Desk</title><link rel="stylesheet" href="style.css"></head>
<body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-icon">B</span><span>Library<span class="brand-light">Desk</span></span></a><a class="top-link" href="index.php">← Back to collection</a></header>
<main class="form-container">
  <p class="eyebrow">BOOK CATALOG</p><h1>Add a new book</h1><p class="subheading">Enter the details below to add a title to your collection.</p>
  <?php if ($errors): ?><div class="alert error"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form class="book-form panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
    <div class="form-grid">
      <div class="field full"><label for="title">Book title <span>*</span></label><input id="title" name="title" maxlength="200" required value="<?= e($title) ?>" placeholder="e.g. The Alchemist"></div>
      <div class="field"><label for="author">Author <span>*</span></label><input id="author" name="author" maxlength="150" required value="<?= e($author) ?>" placeholder="Author's full name"></div>
      <div class="field"><label for="isbn">ISBN</label><input id="isbn" name="isbn" maxlength="30" value="<?= e($isbn) ?>" placeholder="ISBN number (optional)"></div>
      <div class="field"><label for="category">Category</label><input id="category" name="category" maxlength="100" value="<?= e($category) ?>" placeholder="e.g. Programming"></div>
      <div class="field"><label for="price">Price (₹) <span>*</span></label><input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?= e($price) ?>"></div>
      <div class="field"><label for="quantity">Quantity <span>*</span></label><input id="quantity" name="quantity" type="number" min="0" step="1" required value="<?= e($quantity) ?>"></div>
    </div>
    <div class="form-actions"><a class="button button-outline" href="index.php">Cancel</a><button class="button button-primary" type="submit">Save Book</button></div>
  </form>
  <p class="form-hint"><span>*</span> Required fields</p>
</main>
</body></html>
