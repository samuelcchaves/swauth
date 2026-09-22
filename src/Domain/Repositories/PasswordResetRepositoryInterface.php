<?php 


namespace SwAuth\Domain\Repositories;

use PhpParser\Token;
use SwAuth\Domain\Entities\PasswordResetToken;
use SwAuth\Domain\ValueObjects\TokenHash;

interface PasswordResetRepositoryInterface
{
    public function findPendingByTokenHash(TokenHash $tokenHash): ?PasswordResetToken;
    public function supersedePendingForUser(int $userId): void;
    
    public function insert(TokenHash $tokenHash): TokenHash;
    public function update(TokenHash $tokenHash): void;

}

