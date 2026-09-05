<?php

namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;


interface UserRepositoryInterface 
{

    public function findById(int $id): ?User;
    public function findByEmail(Email $email): ?User;

    public function findByUsername(string $email): ?User;

    public function existsByEmail(Email $email): bool;

    public function existsByUsername(string $username): bool;

    public function save(User $user): void;

    


    













}