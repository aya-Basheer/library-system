<?php
namespace LibrarySystem\Entities;

class Member extends User
{
    private \DateTimeImmutable $membershipExpiry;
    private int $borrowLimit;

    public function __construct(string $id, string $name, string $email, ?string $phone, \DateTimeImmutable $expiry, int $borrowLimit = 3)
    {
        parent::__construct($id, $name, $email, $phone);
        $this->membershipExpiry = $expiry;
        $this->borrowLimit = $borrowLimit;
    }

    public function getRole(): string { return 'member'; }
    public function getBorrowLimit(): int { return $this->borrowLimit; }
    public function isMembershipActive(): bool { return $this->membershipExpiry > new \DateTimeImmutable('now'); }
    public function getMembershipExpiry(): \DateTimeImmutable { return $this->membershipExpiry; }
}