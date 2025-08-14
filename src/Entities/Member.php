<?php
namespace LibrarySystem\Entities;

class Member extends User {
    public function __construct(string $name) {
        parent::__construct($name, "Member");
    }

    public function interactWithLibrary(): string {
        return "{$this->name} can borrow books.";
    }
}
