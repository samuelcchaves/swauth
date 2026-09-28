<?php

namespace SwAuth\Application;

use DateTimeImmutable;
use SwAuth\Domain\Entities\User;

final class AccountLockoutPolicy {

    private int $maxAttempts;
    private int $lockDurationMinutes;


    public function __construct(int $maxAttemps = 5, int $lockDurationMinutes = 15) {
        $this->maxAttempts = $maxAttemps;
        $this->lockDurationMinutes = $lockDurationMinutes;    
    }

    public function shouldLock(User $user): bool {
        return $user->getFailedLoginCount() >= $this->maxAttempts;
    }

    public function calculateLockUntil(): DateTimeImmutable {
        return new DateTimeImmutable("+{$this->lockDurationMinutes} minutes");
    }




}