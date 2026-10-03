<?php

namespace SwAuth\Application;



use DateTimeImmutable;

final class IPolicy {
    private int $maxFailures;
    private int $windowMinutes;

    public function __construct(int $maxFailures = 20, int $windowMinutes = 15)
    {
        $this->maxFailures = $maxFailures;
        $this->windowMinutes = $windowMinutes;
    }

    public function isblocked(int $recentFailureCount): bool {
        return $recentFailureCount >= $this->maxFailures;
    }

    public function windowStart(): DateTimeImmutable {
        return new DateTimeImmutable("-{$this->windowMinutes} minutes");
    }
}