<?php

namespace SwAuth\Domain\Entities;

use DateTimeImmutable;

class MfaMethod implements LockableInterface
{

    private int $id;
    private int $userId;

    private string $type;
    private string $secretEncrypted;
    private bool $isActive;
    private int $failedMfaCount;

    private ?DateTimeImmutable $lastUsedAt;


    private ?DateTimeImmutable $lockedUntil;


    public function __construct(int $id, int $userId, string $type, string $secretEncrypted, bool $isActive,int $failedMfaCount, ?DateTimeImmutable $lastUsedAt = null, ?DateTimeImmutable $lockedUntil = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->secretEncrypted = $secretEncrypted;
        $this->isActive = $isActive;
        $this->lastUsedAt = $lastUsedAt;
        $this->failedMfaCount = $failedMfaCount;
        $this->lockedUntil = $lockedUntil;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getSecretEncrypted(): string {
        return $this->secretEncrypted;
    }

    public function getIsActive(): bool {
        return $this->isActive;
    }

    public function getLastUsedAt(): ?DateTimeImmutable 
    {
        return $this->lastUsedAt;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function recordUsage(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
    }

    public function recordFailedMfa(): void 
    {
        $this->failedMfaCount++;
    }
    public function getFailedAttemptCount(): int
    {
        return $this->failedMfaCount;
    }


    public function getLockedUntil(): ?DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function recordSuccessfulMfa(): void
    {
        $this->failedMfaCount = 0;
        $this->lockedUntil = null;
        $this->lastUsedAt = new DateTimeImmutable('now');
    }

    public function lock(DateTimeImmutable $until): void
    {
        $this->lockedUntil = $until;
    }


   public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new DateTimeImmutable('now');
    }

}