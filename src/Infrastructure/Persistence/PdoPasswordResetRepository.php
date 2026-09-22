<?php

namespace SwAuth\Infrastructure\Persistence;

use DateTimeImmutable;
use Override;
use PDO;
use SwAuth\Domain\Entities\PasswordResetToken;
use SwAuth\Domain\Repositories\PasswordResetRepositoryInterface;
use SwAuth\Domain\ValueObjects\TokenHash;


class PdoPasswordResetRepository implements PasswordResetRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?PasswordResetToken
    {
        $stmt = $this->pdo->prepare("SELECT * FROM password_reset_tokens WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToPasswordReset($row);
    }

    #[Override]
    public function findPendingByTokenHash(TokenHash $tokenHash): ?PasswordResetToken
    {
        $stmt = $this->pdo->prepare("SELECT * FROM password_reset_tokens WHERE token_hash = ? and t_status = 'pending'");
        $stmt->execute([(string) $tokenHash]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToPasswordReset($row);
    }

    #[Override]
    public function supersedePendingForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE password_reset_tokens SET t_status = 'superseded', resolved_at = NOW() WHERE user_id = ? and t_status = 'pending'");
        $stmt->execute([$userId]);
    }

    #[Override]
    public function insert(PasswordResetToken $passwordResetToken): PasswordResetToken
    {
        $stmt = $this->pdo->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?,?,?)");
        $stmt->execute([
            $passwordResetToken->getUserId(),
            (string) $passwordResetToken->getTokenHash(),
            $passwordResetToken->getExpiresAt()->format('Y-m-d H:i:s'),
        ]);

        $newId = (int) $this->pdo->lastInsertId();
        return $this->findById($newId);
    }

    #[Override]
    public function update(PasswordResetToken $passwordResetToken): void
    {
        $stmt = $this->pdo->prepare("UPDATE password_reset_tokens SET t_status = ?, resolved_at = ? WHERE id = ?");
        $stmt->execute([
            $passwordResetToken->getStatus(),
            $passwordResetToken->getResolvedAt()?->format('Y-m-d H:i:s'),
            $passwordResetToken->getId(),
        ]);
    }

    private function mapRowToPasswordReset(array $row): PasswordResetToken {

        $expiresAt = new DateTimeImmutable($row['expires_at']);
        $resolvedAt = $row['resolved_at'] !== null ? new DateTimeImmutable($row['resolved_at']) : null;

        return new PasswordResetToken(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            tokenHash: TokenHash::fromStoredHash($row['token_hash']),
            status: $row['t_status'],
            expiresAt: $expiresAt,
            resolvedAt: $resolvedAt,
        );
    }
}