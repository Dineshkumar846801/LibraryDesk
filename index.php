<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM books WHERE title LIKE :title OR author LIKE :author OR isbn LIKE :isbn ORDER BY id DESC'
    );
    $term = '%' . $q . '%';
    $stmt->execute(['title' => $term, 'author' => $term, 'isbn' => $term]);
    $books = $stmt->fetchAll();
} else {
    $books = $pdo->query('SELECT * FROM books ORDER BY id DESC')->fetchAll();
}

$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalCopies = (int)$pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM books')->fetchColumn();

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$status = (string)($_GET['status'] ?? '');
$messages = [
    'added' => 'Book added successfully.',
    'updated' => 'Book updated successfully.',
    'deleted' => 'Book deleted successfully.',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Management | Library Desk</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-icon">B</span><span>Library<span class="brand-light">Desk</span></span></a>
  <span class="top-note">BOOK INVENTORY SYSTEM</span>
</header>

<main class="container">
  <section class="hero">
    <div>
      <p class="eyebrow">YOUR LIBRARY, ORGANIZED</p>
      <h1>Book Management</h1>
      <p class="subheading">Manage your collection, one page at a time.</p>
    </div>
    <a class="button button-primary" href="add.php"><span aria-hidden="true">＋</span> Add New Book</a>
  </section>

  <?php if (isset($messages[$status])): ?>
    <div class="alert success" role="status"><?= e($messages[$status]) ?></div>
  <?php endif; ?>

  <section class="stats">
    <article class="stat-card"><span class="stat-label">BOOK TITLES</span><strong><?= $totalBooks ?></strong><span class="stat-foot">Unique records in catalog</span></article>
    <article class="stat-card"><span class="stat-label">TOTAL COPIES</span><strong><?= $totalCopies ?></strong><span class="stat-foot">Across all book titles</span></article>
    <article class="stat-card"><span class="stat-label">SEARCH RESULTS</span><strong><?= count($books) ?></strong><span class="stat-foot"><?= $q !== '' ? 'Matching your search' : 'Showing all books' ?></span></article>
  </section>

  <section class="panel">
    <div class="panel-heading">
      <div><h2>Book collection</h2><p>View, update, or remove catalog records.</p></div>
      <form class="search-form" method="get" action="index.php">
        <label class="sr-only" for="q">Search books</label>
        <input id="q" type="search" name="q" placeholder="Search title, author, ISBN..." value="<?= e($q) ?>">
        <button class="button button-dark" type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="clear-link" href="index.php">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>BOOK</th><th>AUTHOR</th><th>ISBN</th><th>CATEGORY</th><th>PRICE</th><th>QTY</th><th class="actions-heading">ACTIONS</th></tr></thead>
        <tbody>
        <?php if (!$books): ?>
          <tr><td colspan="7" class="empty-state"><span class="empty-icon">▤</span><strong>No books found</strong><span>Try another search or add a new book.</span></td></tr>
        <?php else: foreach ($books as $book): ?>
          <tr>
            <td><div class="book-title"><?= e($book['title']) ?></div><div class="book-id">ID #<?= (int)$book['id'] ?></div></td>
            <td><?= e($book['author']) ?></td>
            <td class="muted"><?= e($book['isbn'] ?: '—') ?></td>
            <td><?php if ($book['category']): ?><span class="category"><?= e($book['category']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td class="price">₹<?= number_format((float)$book['price'], 2) ?></td>
            <td><span class="quantity"><?= (int)$book['quantity'] ?></span></td>
            <td><div class="action-group">
              <a class="action edit" href="edit.php?id=<?= (int)$book['id'] ?>">Edit</a>
              <form method="post" action="delete.php" onsubmit="return confirm('Delete this book permanently?');">
                <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <button class="action delete" type="submit">Delete</button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-footer">Showing <?= count($books) ?> record(s)<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?> <span>PHP · MySQL · XAMPP</span></div>
  </section>
  <footer>Built with Core PHP and MySQL <span>•</span> Local Library Management</footer>
</main>
</body>
</html>
