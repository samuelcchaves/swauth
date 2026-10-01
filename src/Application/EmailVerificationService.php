<?php

namespace SwAuth\Application;

use SwAuth\Domain\Entities\EmailVerificationToken;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\Repositories\EmailVerificationRepositoryInterface;
use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\ValueObjects\TokenHash;

final class EmailVerificationService {
    private UserRepositoryInterface $userRepo;
    private EmailVerificationRepositoryInterface $emailVericationRepo;


    public function __construct(UserRepositoryInterface $userRepositoryInterface, EmailVerificationRepositoryInterface $emailVerificationRepositoryInterface) {
        $this->userRepo = $userRepositoryInterface;
        $this->emailVericationRepo = $emailVerificationRepositoryInterface;
    }

    public function requestVerification(int $userId): ?string {

        $this->emailVericationRepo->supersedePendingForUser($userId);
        
        $result = EmailVerificationToken::create($userId, 24*60);

        $this->emailVericationRepo->insert($result['entity']);

        return $result['raw'];
    }

    public function verifyEmail(string $raw): void {

        $tokenHash = TokenHash::fromStoredHash(TokenHash::hashRawToken($raw));

        $emailVerify = $this->emailVericationRepo->findPendingByTokenHash($tokenHash);

        if(!$emailVerify) return;
   
        $user = $this->userRepo->findById($emailVerify->getUserId());

        if(!$user) return;

        if(!$emailVerify->isUsable()) return;

        $user->verifyEmail();

        $emailVerify->markAsUsed();

        $this->emailVericationRepo->update($emailVerify);
        $this->userRepo->update($user);

    }

    


}