<?php
namespace LibrarySystem\Entities;

class Librarian extends User {
    public function __construct(string $name) {
        parent::__construct($name, "Librarian");
    }

    public function interactWithLibrary(): string {
        return "{$this->name} can add or remove books.";
    }
}
