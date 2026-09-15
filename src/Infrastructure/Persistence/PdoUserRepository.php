<?php

namespace SwAuth\Infrastructure\Persistence;

use DateTimeImmutable;
use Override;
use PDO;

use SwAuth\Domain\Repositories\UserRepositoryInterface;
USE SwAuth\Domain\Entities\Role;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\Repositories\RoleRepositoryInterface;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;

class PdoUserRepository implements UserRepositoryInterface {
    
    private PDO $pdo;
    private RoleRepositoryInterface $roleRepository;
    public function __construct(PDO $pdo, RoleRepositoryInterface $roleRepository)
    {
        $this->pdo = $pdo;
        $this->roleRepository = $roleRepository;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToUser($row);
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([(string) $email]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToUser($row);
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * from users WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if($row === false) return null;
        return $this->mapRowToUser($row);
    
    }

    public function existsByEmail(Email $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([(string) $email]);
        
        return $stmt->fetch() !== false;
    }


    public function existsByUsername(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        
        return $stmt->fetch() !== false;
    }

    public function insert(User $user): User {
        $stmt = $this->pdo->prepare("INSERT INTO users (role_id, is_active, username, email, first_name, last_name, email_verified, password_hash, must_change_password, failed_login_count)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $user->getRole()->getId(),
            (int) $user->isActive(),
            $user->getUsername(),
            (string) $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            (int) $user->isEmailVerified(),
            (string) $user->getPassword(),
            (int) $user->mustChangePassword(),
            $user->getFailedLoginCount(),
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return $this->findById($newId);
    }

    public function update(User $user): void {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET role_id = ?, is_active = ?,username = ?, email = ?, first_name = ?, last_name = ?, email_verified = ?, password_hash = ?, must_change_password = ?, failed_login_count = ?, locked_until = ?, last_login_at = ? WHERE id = ?"
        );

        $stmt->execute([
            $user->getRole()->getId(),
            (int) $user->isActive(),
            $user->getUsername(),
            (string) $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            (int) $user->isEmailVerified(),
            (string) $user->getPassword(),
            (int) $user->mustChangePassword(),    
            $user->getFailedLoginCount(),
            $user->getLockedUntil()?->format('Y-m-d H:i:s'),
            $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            $user->getId(),
        ]);
    }



    private function mapRowToUser(array $row): User {

        $role = $this->roleRepository->findById((int) $row['role_id']);

        $passwordChangedAt = $row['password_changed_at'] !== null ? new DateTimeImmutable($row['password_changed_at']) : null;
        $lockedUntil = $row['locked_until'] !== null ? new DateTimeImmutable($row['locked_until']) : null;
        $lastLoginAt = $row['last_login_at'] !== null ? new DateTimeImmutable($row['last_login_at']) : null;

        return new User(
            id: (int) $row['id'],
            role: $role,
            isActive: (bool) $row['is_active'],
            username: $row['username'],
            email: new Email($row['email']),
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            emailVerified: (bool) $row['email_verified'],
            password: HashedPassword::fromHash($row['password_hash']),
            passwordChangedAt: $passwordChangedAt,
            mustChangePassword: (bool) $row['must_change_password'],
            failedLoginCount: (int) $row['failed_login_count'],
            lockedUntil: $lockedUntil,
            lastLoginAt: $lastLoginAt,
        );
    }


}



