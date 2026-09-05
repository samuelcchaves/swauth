<?php

namespace SwAuth\Domain\Entities;

use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;


use DateTimeImmutable;

Class User {
    private int $id;
    private Role $role;
    private bool $isActive;
    private string $username;
    private Email $email;
    private ?string $firstName;
    private ?string $lastName;
    private bool $emailVerified;
    private HashedPassword $password;
    private bool $mustChangePassword;
    private int $failed_login_count;

    private ?DateTimeImmutable $lockedUntil;
    private ?DateTimeImmutable $lastLoginAt;

    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_DURATION_MINUTES = 15;


    // Constructor method
    public function __construct(
        int $id,
        Role $role,
        bool $isActive,
        string $username,
        Email $email,
        ?string $firstName,
        ?string $lastName,
        bool $emailVerified,
        HashedPassword $password,
        bool $mustChangePassword,
        int $failed_login_count,
        ?DateTimeImmutable $lockedUntil = null,
        ?DateTimeImmutable $lastLoginAt = null
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getRole(): Role
    {
        return $this->role;
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

    public function getFailedLoginCount(): int
    {
        return $this->failed_login_count;
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
        $this->failed_login_count++;
        if ($this->failed_login_count >= self::MAX_FAILED_ATTEMPTS) {
            $this->lockedUntil = new DateTimeImmutable("+" . self::LOCKOUT_DURATION_MINUTES . " minutes");
        }
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failed_login_count = 0;
        $this->lockedUntil = null;
        $this->lastLoginAt = new DateTimeImmutable();
    }

    public function isLocked(): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }
        return new DateTimeImmutable() < $this->lockedUntil;
    }

    public function verifyEmail(): void 
    {
        $this->emailVerified = true;
    }

    public function markPasswordMustChange(): void 
    {
        $this->mustChangePassword = true;
    }

}