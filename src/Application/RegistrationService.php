<?php

namespace SwAuth\Application;

use SwAuth\Domain\Repositories\RoleRepositoryInterface;
use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;
use SwAuth\Exceptions\UsernameAlreadyExistsException;
use SwAuth\Exceptions\EmailAlreadyExistsException;

final class RegistrationService {

    private UserRepositoryInterface $userRepo;
    private RoleRepositoryInterface $roleRepo;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, RoleRepositoryInterface $roleRepositoryInterface) {
        $this->userRepo = $userRepositoryInterface;
        $this->roleRepo = $roleRepositoryInterface;
    }

    public function register(string $username, string $email, string $plainPassword, string $firstName, string $lastName): User {
        $email = new Email($email);

        if($this->userRepo->findByEmail($email) !== null) {
            throw new EmailAlreadyExistsException("O email {$email} já está a ser utilizado");
        }

        if($this->userRepo->findByUsername($username) !== null) {
            throw new UsernameAlreadyExistsException("O username {$username} já está a ser utilizado.");
        }

        $defaultRole = $this->roleRepo->findByName('user');
        $password = HashedPassword::fromPlainText($plainPassword);

        $user = User::register(
            role: $defaultRole,
            username: $username,
            email: $email,
            hashedPassword: $password,
            firstName: $firstName,
            lastName: $lastName,
        );

        return $this->userRepo->insert($user);
    }
}