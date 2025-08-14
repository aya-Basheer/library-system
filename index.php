<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/vendor/autoload.php';

use LibrarySystem\Entities\{Book, Member, Librarian};
use LibrarySystem\Notifications\EmailNotification;
use LibrarySystem\Services\Library;

// Bootstrap single Library instance in session
if (!isset($_SESSION['lib'])) {
    $_SESSION['lib'] = serialize(new Library());
}
/** @var Library $lib */
$lib = unserialize($_SESSION['lib'], [Library::class]);

// Set notifier once
if (!isset($_SESSION['notifier_set'])) {
    $lib->setNotifier(new EmailNotification());
    $_SESSION['notifier_set'] = true;
}

// Seed first run
if (!isset($_SESSION['seeded'])) {
    $librarian = new Librarian('lib-1', 'Alice Admin', 'alice@example.com');
    $member    = new Member('mem-1', 'Bob Member', 'bob@example.com', '7777777', new DateTimeImmutable('+6 months'));

    $lib->addUser($librarian);
    $lib->addUser($member);

    $lib->addBook(new Book('Clean Code', 'Robert C. Martin', 2008), $librarian);
    $lib->addBook(new Book('The Pragmatic Programmer', 'Andrew Hunt', 1999), $librarian);
    $lib->addBook(new Book('Patterns of Enterprise Application Architecture', 'Martin Fowler', 2002), $librarian);

    $_SESSION['librarian_id'] = $librarian->getId();
    $_SESSION['member_id'] = $member->getId();
    $_SESSION['seeded'] = true;
}

// Helpers
function redirect(): void { header('Location: ' . strtok($_SERVER['REQUEST_URI'],'?')); exit; }

// Handle actions
$action = $_POST['action'] ?? null;
try {
    if ($action === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $librarian = $lib->getUser($_SESSION['librarian_id']);
        if ($librarian instanceof Librarian) {
            $lib->addBook(new Book($title, $author, $year), $librarian);
        }
        redirect();
    }
    if ($action === 'remove_book') {
        $bookId = $_POST['book_id'] ?? '';
        $librarian = $lib->getUser($_SESSION['librarian_id']);
        if ($librarian instanceof Librarian) {
            $lib->removeBook($bookId, $librarian);
        }
        redirect();
    }
    if ($action === 'borrow') {
        $bookId = $_POST['book_id'] ?? '';
        $member = $lib->getUser($_SESSION['member_id']);
        if ($member instanceof Member) {
            $lib->borrow($bookId, $member);
        }
        redirect();
    }
    if ($action === 'return') {
        $bookId = $_POST['book_id'] ?? '';
        $member = $lib->getUser($_SESSION['member_id']);
        if ($member instanceof Member) {
            $_SESSION['last_fee'] = $lib->return($bookId, $member);
        }
        redirect();
    }
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect();
}

// Persist library back
$_SESSION['lib'] = serialize($lib);

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? null;
$books = $search ? $lib->search($search) : $lib->listBooks($sort);
$loans = $lib->getLoans();
$lastFee = $_SESSION['last_fee'] ?? null; unset($_SESSION['last_fee']);
$error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Library System (Simple UI)</title>
  <style>
    body{font-family:system-ui, Arial, sans-serif; margin:20px}
    table{border-collapse:collapse; width:100%; margin-top:10px}
    th,td{border:1px solid #ccc; padding:6px; font-size:14px}
    form{margin:10px 0;}
    .row{display:flex; gap:12px; flex-wrap:wrap}
    .card{border:1px solid #ddd; padding:12px}
    .error{color:#a00}
    .ok{color:#060}
  </style>
</head>
<body>
<h1>Library System</h1>
<?php if ($error): ?><p class="error">Error: <?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($lastFee !== null): ?><p class="ok">Return fee: $<?= number_format((float)$lastFee,2) ?></p><?php endif; ?>

<div class="card">
  <h2>Search & Sort Books</h2>
  <form method="get" class="row">
    <input type="text" name="search" placeholder="title or author" value="<?= htmlspecialchars($search) ?>">
    <select name="sort">
      <option value="">-- sort --</option>
      <option value="title" <?= $sort==='title'?'selected':'' ?>>Title</option>
      <option value="author" <?= $sort==='author'?'selected':'' ?>>Author</option>
      <option value="year" <?= $sort==='year'?'selected':'' ?>>Year</option>
    </select>
    <button type="submit">Apply</button>
  </form>
</div>

<div class="row">
  <div class="card" style="flex:1; min-width:280px;">
    <h2>Add Book (Librarian)</h2>
    <form method="post">
      <input type="hidden" name="action" value="add_book">
      <input required name="title" placeholder="Title">
      <input required name="author" placeholder="Author">
      <input required type="number" name="year" placeholder="Year">
      <button type="submit">Add</button>
    </form>
  </div>

  <div class="card" style="flex:1; min-width:280px;">
    <h2>Borrow / Return (Member)</h2>
    <form method="post" class="row">
      <input type="hidden" name="action" value="borrow">
      <input required name="book_id" placeholder="Book ID to Borrow">
      <button type="submit">Borrow</button>
    </form>
    <form method="post" class="row">
      <input type="hidden" name="action" value="return">
      <input required name="book_id" placeholder="Book ID to Return">
      <button type="submit">Return</button>
    </form>
  </div>
</div>

<h2>Books</h2>
<table>
  <thead>
    <tr><th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Status</th><th>Remove</th></tr>
  </thead>
  <tbody>
  <?php foreach ($books as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b->getId()) ?></td>
      <td><?= htmlspecialchars($b->getTitle()) ?></td>
      <td><?= htmlspecialchars($b->getAuthor()) ?></td>
      <td><?= (int)$b->getYear() ?></td>
      <td><?= $b->isAvailable() ? 'Available' : 'Borrowed' ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Remove book?')">
          <input type="hidden" name="action" value="remove_book">
          <input type="hidden" name="book_id" value="<?= htmlspecialchars($b->getId()) ?>">
          <button type="submit">X</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h2>Active Loans</h2>
<table>
  <thead>
    <tr><th>Book</th><th>Member</th><th>Borrowed</th><th>Due</th><th>Overdue?</th></tr>
  </thead>
  <tbody>
  <?php foreach ($loans as $loan): ?>
    <tr>
      <td><?= htmlspecialchars($loan->getBook()->getTitle()) ?></td>
      <td><?= htmlspecialchars($loan->getMember()->getName()) ?></td>
      <td><?= $loan->getBorrowedAt()->format('Y-m-d') ?></td>
      <td><?= $loan->getDueAt()->format('Y-m-d') ?></td>
      <td><?= $loan->isOverdue() ? 'Yes' : 'No' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<p><small>Logs are written to <code>logs/app.log</code>.</small></p>
</body>
</html>