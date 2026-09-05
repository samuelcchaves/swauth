<?php
namespace SwAuth\Domain\Entities;

class Role {
    private int $id;
    private string $roleName;
    private string $roleDescription;
    private int $level;

    private bool $requires_mfa;

    public function __construct(
        int $id,
        string $roleName,
        string $roleDescription,
        int $level,
        bool $requires_mfa
    ) {
        $this->id = $id;
        $this->roleName = $roleName;
        $this->roleDescription = $roleDescription;
        $this->level = $level;
        $this->requires_mfa = $requires_mfa;
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

    public function getRequiresMfa(): bool
    {
        return $this->requires_mfa;
    }

   
}