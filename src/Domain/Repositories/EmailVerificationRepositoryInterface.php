<?php 


namespace SwAuth\Domain\Repositories;

use Swauth\Domain\Entities\EmailVerificationToken;
use SwAuth\Domain\ValueObjects\TokenHash;


interface EmailVerificationRepositoryInterface
{
    public function findBendingByTokenHash(TokenHash $tokenHash): ?EmailVerificationToken;
    public function save(EmailVerificationToken $token): void;
}