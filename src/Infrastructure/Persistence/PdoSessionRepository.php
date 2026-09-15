<?php

namespace SwAuth\Infrastructure\Persistence;
use DateTimeImmutable;
use PDO;
use SwAuth\Domain\Repositories\SessionRepositoryInterface;
use SwAuth\Domain\Entities\Session;
use SwAuth\Domain\ValueObjects\TokenHash;

class PdoSessionRepository implements SessionRepositoryInterface {

    private PDO $pdo;

    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    public function findByTokenHash(TokenHash $tokenHash): ?Session {
        $stmt = $this->pdo->prepare("SELECT * FROM sessions WHERE session_token_hash = ?");
        $stmt->execute([(string) $tokenHash]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToSession($row);
    }

    public function deleteExpired(): int {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $row = $stmt->execute();

        return $stmt->rowCount();
    }

    public function insert(Session $session): ?Session {
        $stmt = $this->pdo->prepare("INSERT INTO sessions (session_token_hash, user_id, user_agent, ip_created, expires_at, revoked_at) VALUES (?,?,?,?,?,?)");

        $stmt->execute([
            (string) $session->getTokenHash(),
            (int) $session->getUserId(),
            $session->getUserAgent(),
            $session->getIpCreated(),
            $session->getExpiresAt()->format('Y-m-d H:i:s'),
            $session->getRevokedAt()?->format('Y-m-d H:i:s')
        ]);

        $newiD = (int) $this->pdo->lastInsertId();

        return $this->findByTokenHash($session->getTokenHash());
    }

    public function update(Session $session): void {
        $stmt = $this->pdo->prepare("UPDATE sessions SET revoked_at = ? WHERE id = ?");
        $stmt->execute([$session->getRevokedAt()?->format('Y-m-d H:i:s'), $session->getId()]);
    }



    private function mapRowToSession(array $row): Session {
        
        $createdAt = $row['created_at'] !== null ? new DateTimeImmutable($row['created_at']) : null;
        $lastSeenAt = $row['last_seen_at'] !== null ? new DateTimeImmutable($row['last_seen_at']) : null;
        $expiresAt = new DateTimeImmutable($row['expires_at']);
        $revokedAt = $row['revoked_at'] !== null ? new DateTimeImmutable($row['revoked_at']) : null;


        return new Session(
            id: (int) $row['id'],
            tokenHash: TokenHash::fromStoredHash($row['session_token_hash']),
            userId: (int) $row['user_id'],
            userAgent: $row['user_agent'],
            ipCreated: $row['ip_created'],
            expiresAt: $expiresAt,
            revokedAt: $revokedAt
        );
    }
}