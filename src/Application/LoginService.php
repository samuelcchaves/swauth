<?php

namespace SwAuth\Application;


use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Exceptions\AccountLockedException;
use SwAuth\Exceptions\InvalidCredentialsException;
use SwAuth\Application\AccountLockoutPolicy;
use SwAuth\Domain\Repositories\LoginAuditRepositoryInterface;
use SwAuth\Domain\Repositories\MfaMethodRepositoryInterface;
use SwAuth\Exceptions\MfaRequiredException;
use SwAuth\Exceptions\TooManyAttemptsException;
use SwAuth\Application\IPolicy;

final class LoginService {

    private UserRepositoryInterface $userRepo;
    private AccountLockoutPolicy $lockoutPolicy;
    private MfaMethodRepositoryInterface $mfaRepo;
    private MfaService $mfaService;

    private IPolicy $ipolicy;

    private LoginAuditRepositoryInterface $loginAuditRepo;

    public function __construct(UserRepositoryInterface $userRepositoryInterface, AccountLockoutPolicy $lockoutPolicy, MfaMethodRepositoryInterface $mfaMethodRepositoryInterface, MfaService $mfaService, LoginAuditRepositoryInterface $loginAuditRepositoryInterface, IPolicy $ipolicy) {
        $this->userRepo = $userRepositoryInterface; 
        $this->lockoutPolicy = $lockoutPolicy; 
        $this->mfaRepo = $mfaMethodRepositoryInterface;
        $this->mfaService = $mfaService;
        $this->loginAuditRepo = $loginAuditRepositoryInterface;
        $this->ipolicy = $ipolicy;
    }

    public function login(string $email, string $password, string $ip): User {

        if($this->ipolicy->isblocked($this->loginAuditRepo->countRecentFailuresByIp($ip, $this->ipolicy->windowStart()))) {
            $this->loginAuditRepo->record(null, $email, $ip, false, 'ip_blocked');
            throw new TooManyAttemptsException();
        }


        $email = new Email($email);

        $user = $this->userRepo->findByEmail($email);

        if(!$user){
            $this->loginAuditRepo->record(null, $email, $ip, false, 'user_not_found');
            throw new InvalidCredentialsException('Credenciais inválidas');
        }

        if($user->isLocked()) {
            $this->loginAuditRepo->record($user->getId(), $email, $ip, false, 'account_locked');
            throw new AccountLockedException('Conta bloqueada por mais x minutos');
        }


        if(!$user->getPassword()->verify($password) ){
            $this->loginAuditRepo->record($user->getId(), $email, $ip, false, 'invalid_password');
            $user->recordFailedLogin();
            if($this->lockoutPolicy->shouldLock($user)){
                $user->lock($this->lockoutPolicy->calculateLockUntil());
            }
            
            $this->userRepo->update($user);

            if($user->isLocked()){
                $this->loginAuditRepo->record($user->getId(), $email, $ip, false, 'account_got_locked');
                throw new AccountLockedException("A conta foi bloqueada por excesso de tentativas, deve aguardar.");
            }
            
            $this->loginAuditRepo->record($user->getId(), $email, $ip, false, 'invalid_credentials');
            throw new InvalidCredentialsException('Credenciais inválidas');
        }
        $activeMfaMethod = $this->mfaRepo->findActiveByUserId($user->getId());

        if($activeMfaMethod){
            $this->loginAuditRepo->record($user->getId(), $email, $ip, false, 'mfa_validation_required');
            throw new MfaRequiredException($user->getId());
        }

        $user->recordSuccessfulLogin();
        $this->loginAuditRepo->record($user->getId(), $email, $ip, true, 'successful_at_login');
        $this->userRepo->update($user);
        return $user;
        
    }

    public function loginWithMfa(int $userId, string $code, string $ip): User {
        $user = $this->userRepo->findById($userId);

        if (!$this->mfaService->verify($userId, $code)){
            $this->loginAuditRepo->record($userId, $user->getEmail(), $ip, false, 'mfa_validation_error');

            throw new InvalidCredentialsException();
        }
        $user->recordSuccessfulLogin();
        $this->loginAuditRepo->record($user->getId(), $user->getEmail(), $ip, true, 'successful_at_mfa');
        $this->userRepo->update($user);

        return $user;
    }
 
}