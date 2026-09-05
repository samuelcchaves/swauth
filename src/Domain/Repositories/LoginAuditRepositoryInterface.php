<?php 


namespace SwAuth\Domain\Repositories;

use DateTimeImmutable;

interface LoginAuditRepositoryInterface
{
    public function record(?int $userId, string $emailAttemped,string $ip,
    bool $success, ?string $failureReason): void;

    public function countRecentFailuresByUser(int $userId, DateTimeImmutable $since): int;
    public function countRecentFailuresByIp(string $ip, DateTimeImmutable $since): int;

    }