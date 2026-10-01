<?php

namespace SwAuth\Application;

use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Repositories\PasswordResetRepositoryInterface;
use SwAuth\Domain\Entities\PasswordResetToken;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\TokenHash;


final class PasswordResetService {

    private UserRepositoryInterface $userRepo;
    private PasswordResetRepositoryInterface $passwordResetRepo;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, PasswordResetRepositoryInterface $passwordResetRepositoryInterface) {
        $this->userRepo = $userRepositoryInterface;
        $this->passwordResetRepo = $passwordResetRepositoryInterface;
    }

    public function requestReset(string $email): ?string {
        $email = new Email($email);
        $user = $this->userRepo->findByEmail($email);

        if(!$user) return null;

        $this->passwordResetRepo->supersedePendingForUser($user->getId());
        
        $result = PasswordResetToken::create($user->getId(), 30);
        
        $this->passwordResetRepo->insert($result['entity']);

        return $result['raw'];

    }

    public function resetPassword(string $newPassword, string $raw): void {
        

        $tokenHash = TokenHash::fromStoredHash(TokenHash::hashRawToken($raw));

        $passwordReset = $this->passwordResetRepo->findPendingByTokenHash($tokenHash);
        
        if(!$passwordReset) return;

        $user = $this->userRepo->findById($passwordReset->getUserId());

        if(!$user) return;


        if(!$passwordReset->isUsable()) return;

        $user->resetPassword($newPassword);

        $passwordReset->markAsUsed();

        $this->passwordResetRepo->update($passwordReset);
        $this->userRepo->update($user);
    }
   
}