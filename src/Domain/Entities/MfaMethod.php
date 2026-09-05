<?php

namespace SwAuth\Domain\Entities;
use DateTimeImmutable;

class MfaMethod
{
    public function __construct(
        private int $id,
        private int $userId,
        private string $type,             // 'totp' — único valor válido por agora
        private string $secretEncrypted,  // já encriptado; esta classe nunca vê o valor em claro
        private bool $isActive = true,
        private ?DateTimeImmutable $lastUsedAt = null
    )
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->secretEncrypted = $secretEncrypted;
        $this->isActive = true;
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

    public function getLastUsedAt(): DateTimeImmutable 
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