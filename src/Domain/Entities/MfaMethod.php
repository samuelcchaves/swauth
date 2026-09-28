<?php

namespace SwAuth\Domain\Entities;

use DateTime;
use DateTimeImmutable;
use Respect\Validation\Rules\Date;

class MfaMethod
{

    private int $id;
    private int $userId;

    private string $type;
    private string $secretEncrypted;
    private bool $isActive;

    private ?DateTimeImmutable $lastUsedAt;
    public function __construct(int $id, int $userId, string $type, string $secretEncrypted, bool $isActive, ?DateTimeImmutable $lastUsedAt = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->secretEncrypted = $secretEncrypted;
        $this->isActive = $isActive;
        $this->lastUsedAt = $lastUsedAt;
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

}