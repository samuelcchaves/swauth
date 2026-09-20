<?php 

namespace SwAuth\Infrastructure\Persistence;

use DateTimeImmutable;
use Override;
use PDO;

use SwAuth\Domain\Repositories\MfaMethodRepositoryInterface;
use SwAuth\Domain\Entities\MfaMethod;
use SwAuth\Domain\ValueObjects\BackupCode;

class PdoMfaRepository implements MfaMethodRepositoryInterface {

    private PDO $pdo;

    
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    #[Override]
    public function findById(int $id): ?MfaMethod
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mfa_methods WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToMfaMethods($row);
    }

    #[Override]
    public function findActiveByUserId(int $userId): ?MfaMethod
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mfa_methods WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToMfaMethods($row);
    }
    
    public function insert(MfaMethod $mfaMethod): MfaMethod {
        $stmt = $this->pdo->prepare("INSERT INTO mfa_methods (user_id, mfa_type, secret_encrypted, is_active) 
        VALUES (?,?,?,?)");

        $stmt->execute([
            $mfaMethod->getUserId(),
            $mfaMethod->getType(),
            $mfaMethod->getSecretEncrypted(),
            (int) $mfaMethod->getIsActive(),         
        ]);

        $newId = (int) $this->pdo->lastInsertId();
        return $this->findById($newId);
    }

    public function update(MfaMethod $mfaMethod): void {
        $stmt = $this->pdo->prepare(
            "UPDATE mfa_methods SET mfa_type = ?, secret_encrypted = ?, is_active = ? WHERE id = ?"
        );
           
        $stmt->execute([
            $mfaMethod->getType(),
            $mfaMethod->getSecretEncrypted(),
            (int) $mfaMethod->getIsActive(),
            $mfaMethod->getId(),
        ]);
    }

    #[Override]
    public function deactivate(int $mfaMethodId): void
    {
        $stmt = $this->pdo->prepare("UPDATE mfa_methods SET is_active = 0 WHERE id = ?");
        $stmt->execute([$mfaMethodId]);
    }

    #[Override]
    public function saveBackupCode(int $userId, array $codeHashes): void
    {
       foreach($codeHashes as $codeHash){
        $stmt = $this->pdo->prepare("INSERT INTO mfa_backup_codes (user_id, code_hash) VALUES (?, ?)");
        $stmt->execute([$userId, (string) $codeHash]);
       }
    }

    #[Override]
    public function consumeBackupCode(int $userId, BackupCode $codeHash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE mfa_backup_codes SET used_at = NOW() WHERE user_id = ? and code_hash = ? and used_at IS null");
        

        $stmt->execute([$userId,(string) $codeHash]);

        return $stmt->rowCount() > 0;
    
    }

    public function countUnusedBackupCodes(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as Total FROM mfa_backup_codes WHERE user_id = ? and used_at IS null");

        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return (int) $row['Total'];
    }





    private function mapRowToMfaMethods(array $row): MfaMethod {
        
        $lastUsedAt = $row['last_used_at'] !== null ? new DateTimeImmutable($row['last_used_at']) : null;

        return new MfaMethod(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            type: $row['mfa_type'],
            secretEncrypted: $row['secret_encrypted'],
            isActive: (bool) $row['is_active'],
            lastUsedAt: $lastUsedAt,
        );
    }


}