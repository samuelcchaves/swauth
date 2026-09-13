<?php
namespace SwAuth\Domain\Entities;

class Role {
    private int $id;
    private string $roleName;
    private string $roleDescription;
    private int $level;

    private bool $requiresMfa;

    public function __construct(
        int $id,
        string $roleName,
        string $roleDescription,
        int $level,
        bool $requiresMfa
    ) {
        $this->id = $id;
        $this->roleName = $roleName;
        $this->roleDescription = $roleDescription;
        $this->level = $level;
        $this->requiresMfa = $requiresMfa;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoleName(): string
    {
        return $this->roleName;
    }

    public function getRoleDescription(): string
    {
        return $this->roleDescription;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getrequiresMfa(): bool
    {
        return $this->requiresMfa;
    }

   
}