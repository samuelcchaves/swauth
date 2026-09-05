<?php 

namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\MfaMethod;

use SwAuth\Domain\ValueObjects\TokenHash;

interface MfaMethodRepositoryInterface 
{
    public function findActiveByUserId(int $userId): ?MfaMethod;
    public function save(MfaMethod $mfaMethod): void;
    public function deactivate(int $mfaMethodId): void;

    public function saveBackUpCodes(int $userId, array $codeHashes): void;

    public function consumeBackupCode(int $userId, TokenHash $codeHash): bool;

}