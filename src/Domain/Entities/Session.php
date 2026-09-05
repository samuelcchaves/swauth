<?php
namespace SwAuth\Domain\Entities;

use DateTimeImmutable;
use SwAuth\Domain\ValueObjects\TokenHash;

class Session {
    private int $id;
    private int $userId;
    private TokenHash $tokenHash;
    private string $userAgent;
    private string $ipCreated;
    private DateTimeImmutable $expiresAt;
    private DateTimeImmutable $revokedAt;

    public function __construct(
        int $id,
        int $userId,
        TokenHash $tokenHash,
        ?string $userAgent,
        string $ipCreated,
        DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $revokedAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->userAgent = $userAgent;
        $this->ipCreated = $ipCreated;
        $this->expiresAt = $expiresAt;
        $this->revokedAt = $revokedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTokenHash(): TokenHash 
    {
        return $this->tokenHash;
    }

    public function getUserAgent(): string 
    {
        return $this->userAgent;
    }

    public function getIpCreated(): string 
    {
        return $this->ipCreated;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): DateTimeImmutable 
    {
        return $this->revokedAt;
    }

    public function isExpired(): bool 
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isValid(): bool
    {
        return $this->isExpired() && !$this->isRevoked();
    }

    public function revoke(): void 
    {
        $this->revokedAt = new DateTimeImmutable();
    }

    public function matchesToken(string $rawToken): bool
    {
        return $this->tokenHash->matches($rawToken);
    }
    





   
}