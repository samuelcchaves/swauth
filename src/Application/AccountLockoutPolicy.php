<?php

namespace SwAuth\Application;

use DateTimeImmutable;
use SwAuth\Domain\Entities\LockableInterface;

final class AccountLockoutPolicy {

    private int $maxAttempts;
    private int $lockDurationMinutes;

    public function __construct(int $maxAttempts = 5, int $lockDurationMinutes = 15) {
        $this->maxAttempts = $maxAttempts;
        $this->lockDurationMinutes = $lockDurationMinutes;
    }

    public function shouldLock(LockableInterface $entity): bool {
        return $entity->getFailedAttemptCount() >= $this->maxAttempts;
    }

    public function calculateLockUntil(): DateTimeImmutable {
        return new DateTimeImmutable("+{$this->lockDurationMinutes} minutes");
    }
}