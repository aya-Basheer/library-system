<?php
declare(strict_types=1);
// مؤقت لمسح الجلسة بالكامل

session_start();



require __DIR__ . '/vendor/autoload.php';

use LibrarySystem\Services\Library;
use LibrarySystem\Notifications\EmailNotification;
use LibrarySystem\Entities\Member;

// تهيئة المكتبة والمستخدم الحالي (عضو) في السيشن
if (!isset($_SESSION['library'])) {
    $_SESSION['library'] = serialize(new Library(new EmailNotification()));
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = serialize(new Member('Member One'));
}

$library = unserialize($_SESSION['library']);
/** @var Member $currentUser */
$currentUser = unserialize($_SESSION['user']);

// إضافة كتاب
if (isset($_POST['add'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    if ($title !== '' && $author !== '') {
        $library->addBook($title, $author);
    } else {
        $library->notify('الرجاء إدخال العنوان والمؤلف.');
    }
}

// حذف كتاب
if (isset($_GET['delete'])) {
    $library->removeBook($_GET['delete']);
}

// استعارة كتاب
if (isset($_GET['borrow'])) {
    $library->borrowBook($_GET['borrow'], $currentUser);
}

// إرجاع كتاب
if (isset($_GET['return'])) {
    $library->returnBook($_GET['return'], $currentUser);
}

// البحث
$searchResults = [];
$didSearch = false;
if (isset($_GET['search'])) {
    $didSearch = true;
    $keyword = trim($_GET['keyword'] ?? '');
    $searchResults = $library->searchBooks($keyword);
}

$_SESSION['library'] = serialize($library);
$_SESSION['user'] = serialize($currentUser);

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Library System</title>
</head>
<body>
    <h1>📚 Library Management</h1>
    <p>المستخدم الحالي: <strong><?= htmlspecialchars($currentUser->getName()) ?></strong> (Member)</p>

    <!-- إضافة كتاب -->
    <form method="POST" style="margin-bottom:10px;">
        <input type="text" name="title" placeholder="Book Title" required>
        <input type="text" name="author" placeholder="Author" required>
        <button type="submit" name="add">Add Book</button>
    </form>

    <!-- البحث -->
    <form method="GET" style="margin-bottom:10px;">
        <input type="text" name="keyword" placeholder="Search by title or author" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
        <button type="submit" name="search">Search</button>
    </form>

    <h2>Books List</h2>
    <?php if ($didSearch && empty($searchResults)): ?>
        <p><strong>لا توجد نتائج للبحث.</strong></p>
    <?php endif; ?>

    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th><th>Title</th><th>Author</th><th>Status</th><th>Actions</th>
        </tr>
        <?php
        $booksToShow = $didSearch ? $searchResults : $library->getBooks();
        foreach ($booksToShow as $book): ?>
        <tr>
            <td><?= htmlspecialchars($book->getId()) ?></td>
            <td><?= htmlspecialchars($book->getTitle()) ?></td>
            <td><?= htmlspecialchars($book->getAuthor()) ?></td>
            <td><?= $book->isAvailable() ? 'Available' : 'Borrowed' ?></td>
            <td>
                <?php if ($book->isAvailable()): ?>
                    <a href="?borrow=<?= urlencode($book->getId()) ?>">📥 Borrow</a>
                <?php else: ?>
                    <a href="?return=<?= urlencode($book->getId()) ?>">↩️ Return</a>
                <?php endif; ?>
                &nbsp;|&nbsp;
                <a href="?delete=<?= urlencode($book->getId()) ?>" onclick="return confirm('Delete this book?')">❌ Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
