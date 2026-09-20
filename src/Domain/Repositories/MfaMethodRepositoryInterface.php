<?php 

namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\MfaMethod;

use SwAuth\Domain\ValueObjects\BackupCode;

interface MfaMethodRepositoryInterface 
{

    public function findById(int $userId): ?MfaMethod;

    public function findActiveByUserId(int $userId): ?MfaMethod;

    public function insert(MfaMethod $mfaMethod): ?MfaMethod;

    public function update(MfaMethod $mfaMethod): void;

    public function deactivate(int $mfaMethodId): void;
    /**
     *  @param BackupCode[] $codeHashes
    */
    public function saveBackupCode(int $userId, array $codeHashes): void;
    public function consumeBackupCode(int $userId, BackupCode $codeHash): bool;

}