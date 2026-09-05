<?php 


namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\PasswordResetToken;
use Swauth\Domain\ValueObjects\TokenHash;

interface PasswordResetRepositoryInterface
{
    public function findPendingByTokenHash(TokenHash $tokenHash): ?PasswordResetToken;
    public function save(PasswordResetToken $token): void;
    public function supersedePendingForUser(int $userId): void;
    
}

