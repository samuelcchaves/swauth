<?php

namespace SwAuth\Application;

use DateTimeImmutable;
use SwAuth\Domain\Entities\Session;
use SwAuth\Domain\Repositories\SessionRepositoryInterface;
use SwAuth\Domain\ValueObjects\TokenHash;


final class SessionService {
    
    private SessionRepositoryInterface $sessionRepo;
    private int $sessionDuration;
    public function __construct(SessionRepositoryInterface $sessionRepo, int $sessionDuration){
        $this->sessionRepo = $sessionRepo;
        $this->sessionDuration = $sessionDuration;
    }


    public function create(int $userId, ?string $userAgent, string $ipCreated): string {
        $tokenData = TokenHash::generate();
        $raw = $tokenData['raw'];
        $tokenHash = $tokenData['hash'];

        $expiresAt = new DateTimeImmutable("+{$this->sessionDuration} hours");

        $session = new Session(id: 0, userId: $userId, tokenHash: $tokenHash, userAgent: $userAgent, ipCreated: $ipCreated, expiresAt: $expiresAt, revokedAt:null);

        $this->sessionRepo->insert($session);

        return $raw;
    }

    public function validate(string $rawToken): ?int {
        $hash = TokenHash::hashRawToken($rawToken);
        $tokenHash = TokenHash::fromStoredHash($hash);

        $session = $this->sessionRepo->findByTokenHash($tokenHash);

        if($session === null) {
            return null;
        }

        if(!$session->isValid()){
            return null;
        }

        return $session->getUserId();
    }


    public function revoke(string $rawToken): void {
        $hash = TokenHash::hashRawToken($rawToken);
        $tokenHash = TokenHash::fromStoredHash($hash);
    
        $session = $this->sessionRepo->findByTokenHash($tokenHash);

        if($session === null) {
            return;
        }

        $session->revoke();
        $this->sessionRepo->update($session);
    }


}

