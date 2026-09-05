<?php

namespace SwAuth\Domain\Entities;

use DateTimeImmutable;
use SwAuth\Domain\ValueObjects\TokenHash;

class EmailResetToken
{
    public function __construct(
        private int $id,
        private int $userId,
        private TokenHash $tokenHash,
        private string $status,          // 'pending' | 'used' | 'superseded'
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $resolvedAt = null
    ) {}

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTokenHash(): TokenHash { return $this->tokenHash; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUsable(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function markAsUsed(): void
    {
        $this->status = 'used';
        $this->resolvedAt = new DateTimeImmutable();
    }

    public function markAsSuperseded(): void
    {
        $this->status = 'superseded';
        $this->resolvedAt = new DateTimeImmutable();
    }
}