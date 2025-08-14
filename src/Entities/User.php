<?php
namespace LibrarySystem\Entities;

abstract class User
{
    protected string $id;
    protected string $name;
    // Encapsulation for sensitive data (email, phone)
    private string $email;
    private ?string $phone;

    public function __construct(string $id, string $name, string $email, ?string $phone = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }

    abstract public function getRole(): string;
}