<?php

namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\Session;
use SwAuth\Domain\ValueObjects\TokenHash;

interface SessionRepositoryInterface {
    public function findByTokenHash(TokenHash $tokenHash): ?Session;
    public function save(Session $session): void;
    public function deleteExpired(): int;

}