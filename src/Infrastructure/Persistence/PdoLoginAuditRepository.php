<?php


namespace SwAuth\Infrastructure\Persistence;

use DateTimeImmutable;
use SwAuth\Domain\Repositories\LoginAuditRepositoryInterface;
use PDO;



class PdoLoginAuditRepository implements LoginAuditRepositoryInterface {

    private PDO $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function record(?int $userId, string $emailAttempted, string $ip, bool $success, ?string $failureReason): void {
        $stmt = $this->pdo->prepare("INSERT INTO login_audit (user_id, email_attempted, ip, success, failure_reason) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $emailAttempted, $ip, (int) $success, $failureReason]);
    }

    public function countRecentFailuresByUser(int $userId, DateTimeImmutable $since): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM login_audit WHERE user_id = ? AND created_at >= ?");
        $stmt->execute([$userId, (string) $since]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function countRecentFailuresByIp(string $ip, DateTimeImmutable $since): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM login_audit WHERE ip = ? AND created_at >= ?");
        $stmt->execute([$ip, (string) $since]);
        
        return (int) $stmt->fetchColumn();
    }  

    

}



