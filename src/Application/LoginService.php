<?php

namespace SwAuth\Application;

use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;
use SwAuth\Exceptions\AccountLockedException;
use SwAuth\Exceptions\InvalidCredentialsException;
use SwAuth\Application\AccountLockoutPolicy;


final class LoginService {

    private UserRepositoryInterface $userRepo;
    private AccountLockoutPolicy $lockoutPolicy;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AccountLockoutPolicy $lockoutPolicy) {
        $this->userRepo = $userRepositoryInterface; 
        $this->lockoutPolicy = $lockoutPolicy;
    }

    public function login(string $email, string $password): User {
        $email = new Email($email);

        $user = $this->userRepo->findByEmail($email);

        if(!$user){
            throw new InvalidCredentialsException('Credenciais inválidas');
        }

        if($user->isLocked()) {
            throw new AccountLockedException('Conta bloqueada por mais x minutos');
        }


        if(!$user->getPassword()->verify($password) ){
            $user->recordFailedLogin();
            if($this->lockoutPolicy->shouldLock($user)){
                $user->lock($this->lockoutPolicy->calculateLockUntil());
            }
            
            $this->userRepo->update($user);

            if($user->isLocked()){
                throw new AccountLockedException("A conta foi bloqueada por excesso de tentativas, deve aguardar.");
            }

            throw new InvalidCredentialsException('Credenciais inválidas');
        }

        $user->recordSuccessfulLogin();
        $this->userRepo->update($user);
    

        return $user;
        
    }
 
}