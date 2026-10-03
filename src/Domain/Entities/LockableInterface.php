<?php

namespace SwAuth\Domain\Entities;

use DateTimeImmutable;

interface LockableInterface {
    public function getFailedAttemptCount(): int;

    public function lock(DateTimeImmutable $until): void;
    
}