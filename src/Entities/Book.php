<?php
namespace LibrarySystem\Entities;

class Book {
    private string $id;
    private string $title;
    private string $author;
    private bool $isBorrowed = false;

    public function __construct(string $id, string $title, string $author) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
    }

    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getAuthor(): string { return $this->author; }

    public function isAvailable(): bool { return !$this->isBorrowed; }
    public function borrow(): void { $this->isBorrowed = true; }
    public function returnBook(): void { $this->isBorrowed = false; }
}
