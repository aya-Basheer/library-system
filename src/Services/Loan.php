<?php
namespace LibrarySystem\Services;

use LibrarySystem\Entities\Book;
use LibrarySystem\Entities\Member;

class Loan
{
    private Book $book;
    private Member $member;
    private \DateTimeImmutable $borrowedAt;
    private \DateTimeImmutable $dueAt;
    private ?\DateTimeImmutable $returnedAt = null;

    public function __construct(Book $book, Member $member, int $loanDays = 14)
    {
        $this->book = $book;
        $this->member = $member;
        $this->borrowedAt = new \DateTimeImmutable('now');
        $this->dueAt = $this->borrowedAt->modify("+$loanDays days");
    }

    public function getBook(): Book { return $this->book; }
    public function getMember(): Member { return $this->member; }
    public function getBorrowedAt(): \DateTimeImmutable { return $this->borrowedAt; }
    public function getDueAt(): \DateTimeImmutable { return $this->dueAt; }
    public function getReturnedAt(): ?\DateTimeImmutable { return $this->returnedAt; }

    public function markReturned(): void { $this->returnedAt = new \DateTimeImmutable('now'); }

    public function isOverdue(): bool
    {
        $ref = $this->returnedAt ?: new \DateTimeImmutable('now');
        return $ref > $this->dueAt;
    }

    public function calculateLateFee(float $perDay = 0.5): float
    {
        $end = $this->returnedAt ?: new \DateTimeImmutable('now');
        if ($end <= $this->dueAt) return 0.0;
        $days = (int) $this->dueAt->diff($end)->format('%a');
        return round($days * $perDay, 2);
    }
}