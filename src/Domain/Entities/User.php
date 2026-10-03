<?php

namespace SwAuth\Domain\Entities;

use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;


use DateTimeImmutable;
use Override;
use SwAuth\Domain\ValueObjects\TokenHash;
use SwAuth\Exceptions\InvalidPasswordMatchException;

class User implements LockableInterface{
    private int $id;
    private bool $isActive;
    private string $username;
    private Email $email;
    private ?string $firstName;
    private ?string $lastName;
    private bool $emailVerified;
    private HashedPassword $password;
    private bool $mustChangePassword;
    private int $failedLoginCount;
    private ?DateTimeImmutable $passwordChangedAt;

    private ?DateTimeImmutable $lockedUntil;
    private ?DateTimeImmutable $lastLoginAt;




    // Constructor method
    public function __construct(
        int $id,
        bool $isActive,
        string $username,
        Email $email,
        ?string $firstName,
        ?string $lastName,
        bool $emailVerified,
        HashedPassword $password,
        bool $mustChangePassword,
        int $failedLoginCount,
        ?DateTimeImmutable $passwordChangedAt,
        ?DateTimeImmutable $lockedUntil = null,
        ?DateTimeImmutable $lastLoginAt = null
    ) {
        $this->id = $id;
        $this->isActive = $isActive;
        $this->username = $username;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->emailVerified = $emailVerified;
        $this->password = $password;
        $this->mustChangePassword = $mustChangePassword;
        $this->failedLoginCount = $failedLoginCount;
        $this->passwordChangedAt = $passwordChangedAt;
        $this->lockedUntil = $lockedUntil;
        $this->lastLoginAt = $lastLoginAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function getPasswordChangedAt(): ?DateTimeImmutable 
    {
        return $this->passwordChangedAt;
    }

    #[Override]
    public function getFailedAttemptCount(): int
    {
        return $this->failedLoginCount;

    }

    public function getLockedUntil(): ?DateTimeImmutable
    {
        return $this->lockedUntil;
    }
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function recordFailedLogin(): void 
    {
        $this->failedLoginCount++;
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failedLoginCount = 0;
        $this->lockedUntil = null;
        $this->lastLoginAt = new DateTimeImmutable('now');
    }

    public function lock(DateTimeImmutable $until): void
    {
        $this->lockedUntil = $until;
    }


   public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new DateTimeImmutable('now');
    }


    public function verifyEmail(): void 
    {
        $this->emailVerified = true;
    }

    public function markPasswordMustChange(): void 
    {
        $this->mustChangePassword = true;
    }

    public function changePassword(string $newPassword, string $confirmPassword): void
    {
        if(!$this->password->verify($confirmPassword)){
            throw new InvalidPasswordMatchException;
        }

        $this->password = HashedPassword::fromPlainText($newPassword); 
        $this->passwordChangedAt = new DateTimeImmutable('now');
        $this->mustChangePassword = false;

    }

    public function resetPassword(string $newPassword): void
    {
    
        $this->password = HashedPassword::fromPlainText($newPassword); 
        $this->passwordChangedAt = new DateTimeImmutable('now');
        $this->mustChangePassword = false;

    }

    public static function register(string $username, Email $email, string $plainPassword, string $firstName, string $lastName): self {
        
        $hashedPassword = HashedPassword::fromPlainText($plainPassword);

        return new self(
            id: 0, isActive: true, username: $username, email: $email, firstName: $firstName, lastName: $lastName, emailVerified: false,
            password: $hashedPassword, mustChangePassword: false, failedLoginCount: 0, passwordChangedAt:null, lockedUntil: null, lastLoginAt: null
        );
    }



}