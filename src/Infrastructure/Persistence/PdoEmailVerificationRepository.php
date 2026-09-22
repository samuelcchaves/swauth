<?php 

namespace Src\Infrastructure\Persistence;


use DateTimeImmutable;

use PDO;
use Override;

use SwAuth\Domain\Entities\EmailVerificationToken;
use SwAuth\Domain\Repositories\EmailVerificationRepositoryInterface;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\TokenHash;



class PdoEmailVerificationRepository implements EmailVerificationRepositoryInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

     public function findPendingByTokenHash(TokenHash $tokenHash): ?EmailVerificationToken
    {
        $stmt = $this->pdo->prepare("SELECT * FROM password_reset_tokens WHERE token_hash = ? and t_status = 'pending'");
        $stmt->execute([(string) $tokenHash]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToEmailVerification($row);
    }

     public function insert(EmailVerificationToken $emailVerificationToken): EmailVerificationToken
    {
        $stmt = $this->pdo->prepare("INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?,?,?)");
        $stmt->execute([
            $emailVerificationToken->getUserId(),
            (string) $emailVerificationToken->getTokenHash(),
            $emailVerificationToken->getExpiresAt()->format('Y-m-d H:i:s'),
        ]);

        

        $newId = (int) $this->pdo->lastInsertId();
        return new EmailVerificationToken(
            id: $newId,
            userId: $emailVerificationToken->getUserId(),
            tokenHash: TokenHash::fromStoredHash($emailVerificationToken->getTokenHash()),
            status: $emailVerificationToken->getStatus(),
            expiresAt: $emailVerificationToken->getExpiresAt(),
            resolvedAt: $emailVerificationToken->getResolvedAt()
        );
    }

    #[Override]
    public function update(EmailVerificationToken $emailVerificationToken): void
    {
        $stmt = $this->pdo->prepare("UPDATE email_verification_tokens SET t_status = ?, resolved_at = ? WHERE id = ?");
        $stmt->execute([
            $emailVerificationToken->getStatus(),
            $emailVerificationToken->getResolvedAt()?->format('Y-m-d H:i:s'),
            $emailVerificationToken->getId(),
        ]);
    }
    private function mapRowToEmailVerification(array $row): EmailVerificationToken {

        $expiresAt = new DateTimeImmutable($row['expires_at']);
        $resolvedAt = $row['resolved_at'] !== null ? new DateTimeImmutable($row['resolved_at']) : null;

        return new EmailVerificationToken(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            tokenHash: TokenHash::fromStoredHash($row['token_hash']),
            status: $row['t_status'],
            expiresAt: $expiresAt,
            resolvedAt: $resolvedAt,
        );
    }
}

