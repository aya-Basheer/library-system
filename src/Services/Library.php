<?php
namespace LibrarySystem\Services;

use LibrarySystem\Entities\{Book, User, Member, Librarian};
use LibrarySystem\Interfaces\NotificationChannel;
use LibrarySystem\Traits\LoggerTrait;

class Library
{
    use LoggerTrait;

    /** @var array<string,Book> */
    private array $books = [];
    /** @var array<string,User> */
    private array $users = [];
    /** @var array<string,Loan> Active loans indexed by bookId */
    private array $loans = [];

    private ?NotificationChannel $notifier = null;

    public function setNotifier(NotificationChannel $channel): void
    {
        $this->notifier = $channel;
    }

    // Books
    public function addBook(Book $book, User $by): void
    {
        if (!$by instanceof Librarian) {
            throw new \RuntimeException('Only librarians can add books');
        }
        $this->books[$book->getId()] = $book;
        $this->log("Book added: {$book->getTitle()} by {$by->getName()}");
    }

    public function removeBook(string $bookId, User $by): void
    {
        if (!$by instanceof Librarian) {
            throw new \RuntimeException('Only librarians can remove books');
        }
        unset($this->books[$bookId]);
        $this->log("Book removed: $bookId by {$by->getName()}");
    }

    /** @return Book[] */
    public function search(string $term): array
    {
        $t = mb_strtolower(trim($term));
        return array_values(array_filter($this->books, function (Book $b) use ($t) {
            return str_contains(mb_strtolower($b->getTitle()), $t)
                || str_contains(mb_strtolower($b->getAuthor()), $t);
        }));
    }

    /** @return Book[] */
    public function listBooks(?string $sortBy = null): array
    {
        $books = array_values($this->books);
        if ($sortBy === 'title') {
            usort($books, fn($a,$b)=>strcmp($a->getTitle(), $b->getTitle()));
        } elseif ($sortBy === 'author') {
            usort($books, fn($a,$b)=>strcmp($a->getAuthor(), $b->getAuthor()));
        } elseif ($sortBy === 'year') {
            usort($books, fn($a,$b)=>$a->getYear() <=> $b->getYear());
        }
        return $books;
    }

    // Users
    public function addUser(User $user): void
    {
        $this->users[$user->getId()] = $user;
        $this->log("User added: {$user->getName()} ({$user->getRole()})");
    }

    public function getUser(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    // Borrow / Return (role-specific behavior)
    public function borrow(string $bookId, Member $member): Loan
    {
        if (!$member->isMembershipActive()) {
            throw new \RuntimeException('Membership expired');
        }
        $book = $this->books[$bookId] ?? null;
        if (!$book || !$book->isAvailable()) {
            throw new \RuntimeException('Book not available');
        }
        // enforce borrow limit
        $currentLoans = array_filter($this->loans, fn(Loan $l)=>$l->getMember()->getId()===$member->getId() && $l->getReturnedAt()===null);
        if (count($currentLoans) >= $member->getBorrowLimit()) {
            throw new \RuntimeException('Borrow limit reached');
        }

        $loan = new Loan($book, $member);
        $this->loans[$book->getId()] = $loan;
        $book->markBorrowed();
        $this->log("Borrowed: {$book->getTitle()} by {$member->getName()}");

        if ($this->notifier) {
            $this->notifier->send($member->getEmail(), 'Borrowed Book', 'You borrowed: '.$book->getTitle());
        }
        return $loan;
    }

    public function return(string $bookId, Member $member): float
    {
        $loan = $this->loans[$bookId] ?? null;
        if (!$loan || $loan->getMember()->getId() !== $member->getId()) {
            throw new \RuntimeException('No active loan for this member/book');
        }
        $loan->markReturned();
        $loan->getBook()->markReturned();
        $fee = $loan->calculateLateFee();
        $this->log("Returned: {$loan->getBook()->getTitle()} by {$member->getName()} | Fee: $fee");
        return $fee;
    }

    /** @return Loan[] */
    public function getLoans(): array
    {
        return array_values($this->loans);
    }
}