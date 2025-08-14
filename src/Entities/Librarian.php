<?php
namespace LibrarySystem\Entities;

class Librarian extends User
{
    public function getRole(): string { return 'librarian'; }
}