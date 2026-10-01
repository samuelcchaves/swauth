<?php 

namespace SwAuth\Domain\Entities;

use DateTimeImmutable;
use SwAuth\Domain\ValueObjects\TokenHash;

abstract class ExpiringToken 
{
    protected int $id;
    protected int $userId;

    protected TokenHash $tokenHash;
    protected string $status;
    protected DateTimeImmutable $expiresAt;
    protected ?DateTimeImmutable $resolvedAt = null;

    public function __construct(int $id, int $userId, TokenHash $tokenHash, string $status, DateTimeImmutable $expiresAt, ?DateTimeImmutable $resolvedAt = null) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
        $this->resolvedAt = $resolvedAt;
    }
 public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTokenHash(): TokenHash { return $this->tokenHash; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function getExpiresAt(): DateTimeImmutable {
        return $this->expiresAt;
    }

    public function getStatus(): String {
        return $this->status;
    }

    public function getResolvedAt(): ?DateTimeImmutable {
        return $this->resolvedAt;
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

       public static function create(int $userId, int $validFor): array {
        $pair = TokenHash::generate();
        
        $token = new static(
            id: 0,
            userId: $userId,
            tokenHash: $pair['hash'],
            status: 'pending',
            expiresAt: new DateTimeImmutable("+{$validFor} minutes"),
        );

        return [
            'raw' => $pair['raw'],
            'entity' => $token,
        ];    
    }
    
}