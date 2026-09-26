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
    private int $failedLoginCount;
    private ?DateTimeImmutable $passwordChangedAt;

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
        int $failedLoginCount,
        ?DateTimeImmutable $passwordChangedAt,
        ?DateTimeImmutable $lockedUntil = null,
        ?DateTimeImmutable $lastLoginAt = null
    ) {
        $this->id = $id;
        $this->role = $role;
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

    public function getPasswordChangedAt(): ?DateTimeImmutable 
    {
        return $this->passwordChangedAt;
    }

    public function getFailedLoginCount(): int
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
        if ($this->failedLoginCount >= self::MAX_FAILED_ATTEMPTS) {
            $this->lockedUntil = new DateTimeImmutable("+" . self::LOCKOUT_DURATION_MINUTES . " minutes");
        }
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failedLoginCount = 0;
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

    public static function register(Role $role, string $username, Email $email, HashedPassword $hashedPassword, string $firstName, string $lastName): self {
        return new self(
            id: 0, role: $role, isActive: true, username: $username, email: $email, firstName: $firstName, lastName: $lastName, emailVerified: false,
            password: $hashedPassword, mustChangePassword: false, failedLoginCount: 0, passwordChangedAt:null, lockedUntil: null, lastLoginAt: null
        );
    }



}