<?php

namespace SwAuth\Application;

use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;
use SwAuth\Exceptions\AccountLockedException;
use SwAuth\Exceptions\InvalidCredentialsException;
use SwAuth\Application\AccountLockoutPolicy;
use SwAuth\Domain\Repositories\MfaMethodRepositoryInterface;
use SwAuth\Exceptions\MfaRequiredException;

final class LoginService {

    private UserRepositoryInterface $userRepo;
    private AccountLockoutPolicy $lockoutPolicy;
    private MfaMethodRepositoryInterface $mfaRepo;
    private MfaService $mfaService;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AccountLockoutPolicy $lockoutPolicy, MfaMethodRepositoryInterface $mfaMethodRepositoryInterface, MfaService $mfaService) {
        $this->userRepo = $userRepositoryInterface; 
        $this->lockoutPolicy = $lockoutPolicy; 
        $this->mfaRepo = $mfaMethodRepositoryInterface;
        $this->mfaService = $mfaService;
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
        $activeMfaMethod = $this->mfaRepo->findActiveByUserId($user->getId());
        if($activeMfaMethod){
            throw new MfaRequiredException($user->getId());
        }

        $user->recordSuccessfulLogin();
        $this->userRepo->update($user);
        return $user;
        
    }

    private function loginWithMfa(int $userId, string $code): User {
        if (!$this->mfaService->verify($userId, $code)){
            throw new InvalidCredentialsException();
        }

        $user = $this->userRepo->findById($userId);
        $user->recordFailedLogin();
        $this->userRepo->update($user);

        return $user;
    }
 
}