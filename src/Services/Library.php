<?php
namespace LibrarySystem\Services;

use LibrarySystem\Entities\Book;
use LibrarySystem\Entities\User;
use LibrarySystem\Entities\Member;
use LibrarySystem\Traits\LoggerTrait;
use LibrarySystem\Traits\IdGeneratorTrait;
use LibrarySystem\Notifications\NotificationInterface;

class Library {
    use LoggerTrait, IdGeneratorTrait;

    /** @var Book[] */
    private array $books = [];
    private NotificationInterface $notifier;

    public function __construct(NotificationInterface $notifier) {
        $this->notifier = $notifier;
    }

    public function notify(string $message): void {
        $this->notifier->send($message);
    }

    public function addBook(string $title, string $author): void {
        $book = new Book($this->generateId(), $title, $author);
        $this->books[] = $book;
        $this->log("Book '{$title}' added.");
        $this->notify("تمت إضافة الكتاب: '{$title}'.");
    }

    /** @return Book[] */
    public function getBooks(): array {
        return $this->books;
    }

    /** @return Book[] */
    public function searchBooks(string $keyword): array {
        $keyword = trim($keyword);
        if ($keyword === '') return $this->books;

        $results = array_filter($this->books, function (Book $book) use ($keyword) {
            return stripos($book->getTitle(), $keyword) !== false
                || stripos($book->getAuthor(), $keyword) !== false;
        });

        if (!$results) {
            $this->notify("لا يوجد كتاب يطابق: '{$keyword}'.");
        }
        return $results;
    }

    public function removeBook(string $id): void {
        foreach ($this->books as $i => $book) {
            if ($book->getId() === $id) {
                unset($this->books[$i]);
                $this->books = array_values($this->books);
                $this->log("Book with ID {$id} removed.");
                $this->notify("تم حذف الكتاب (ID: {$id}).");
                return;
            }
        }
        $this->notify("الكتاب المطلوب حذفه غير موجود (ID: {$id}).");
    }

    public function borrowBook(string $id, User $user): void {
        if (!($user instanceof Member)) {
            $this->notify("فقط الأعضاء يمكنهم الاستعارة.");
            return;
        }

        foreach ($this->books as $book) {
            if ($book->getId() === $id) {
                if (!$book->isAvailable()) {
                    $this->notify("هذا الكتاب مُستعار بالفعل.");
                    return;
                }
                $book->borrow();
                $this->log("{$user->getName()} borrowed '{$book->getTitle()}' (ID: {$id}).");
                $this->notify("تمت الاستعارة: '{$book->getTitle()}'.");
                return;
            }
        }
        $this->notify("الكتاب غير موجود (ID: {$id}).");
    }

    public function returnBook(string $id, User $user): void {
        foreach ($this->books as $book) {
            if ($book->getId() === $id) {
                if ($book->isAvailable()) {
                    $this->notify("هذا الكتاب غير مُستعار.");
                    return;
                }
                $book->returnBook();
                $this->log("{$user->getName()} returned '{$book->getTitle()}' (ID: {$id}).");
                $this->notify("تمت إعادة الكتاب: '{$book->getTitle()}'.");
                return;
            }
        }
        $this->notify("الكتاب غير موجود (ID: {$id}).");
    }
}
