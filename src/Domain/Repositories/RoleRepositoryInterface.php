<?php

namespace SwAuth\Domain\Repositories;

use SwAuth\Domain\Entities\Role;

interface RoleRepositoryInterface {
    public function findById(int $id): ?Role;
    public function findByName(string $roleName): ?Role;
    public function findAll(): array;


}