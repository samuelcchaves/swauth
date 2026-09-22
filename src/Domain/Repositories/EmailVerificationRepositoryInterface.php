<?php 


namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\EmailVerificationToken;
use SwAuth\Domain\ValueObjects\TokenHash;


interface EmailVerificationRepositoryInterface
{
    public function findPendingByTokenHash(TokenHash $tokenHash): ?EmailVerificationToken;
    public function insert(EmailVerificationToken $token): EmailVerificationToken;
    public function update(EmailVerificationToken $token): void;

    public function supersedePendingForUser(int $userId): void;

}