<?php

namespace SwAuth\Application;

use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Entities\User;
use SwAuth\Exceptions\UserNotFoundException;

final class ChangePasswordService {

    private UserRepositoryInterface $userRepo;

    public function __construct(UserRepositoryInterface $userRepositoryInterface) {
        $this->userRepo = $userRepositoryInterface;
    }

    public function changePassword(int $userId, string $newPassword, string $confirmPassword ): void {
        $user = $this->userRepo->findById($userId);

        if(!$user) throw new UserNotFoundException();

        $user->changePassword($newPassword, $confirmPassword);


        $this->userRepo->update($user);
    }
}