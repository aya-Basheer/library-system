<?php
namespace LibrarySystem\Entities;

use LibrarySystem\Traits\UuidTrait;

class Book
{
    use UuidTrait;

    private string $id;
    private string $title;
    private string $author;
    private int $year;
    private bool $available = true;

    public function __construct(string $title, string $author, int $year)
    {
        $this->id = $this->uuid();
        $this->title = $title;
        $this->author = $author;
        $this->year = $year;
    }

    // Encapsulation: getters/setters
    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): void { $this->title = $t; }
    public function getAuthor(): string { return $this->author; }
    public function setAuthor(string $a): void { $this->author = $a; }
    public function getYear(): int { return $this->year; }
    public function setYear(int $y): void { $this->year = $y; }
    public function isAvailable(): bool { return $this->available; }
    public function markBorrowed(): void { $this->available = false; }
    public function markReturned(): void { $this->available = true; }
}