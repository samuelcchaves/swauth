<?php

namespace SwAuth;

use PDO;
use RuntimeException;
use SwAuth\Application\AccountLockoutPolicy;
use SwAuth\Application\ChangePasswordService;
use SwAuth\Application\EmailVerificationService;
use SwAuth\Application\LoginService;
use SwAuth\Application\MfaService;
use SwAuth\Application\PasswordResetService;
use SwAuth\Application\RegistrationService;
use SwAuth\Application\SessionService;
use SwAuth\Domain\Repositories\EmailVerificationRepositoryInterface;
use SwAuth\Domain\Repositories\MfaMethodRepositoryInterface;
use SwAuth\Domain\Repositories\PasswordResetRepositoryInterface;
use SwAuth\Domain\Repositories\RoleRepositoryInterface;
use SwAuth\Domain\Repositories\SessionRepositoryInterface;
use SwAuth\Domain\Repositories\UserRepositoryInterface;
use SwAuth\Infrastructure\Persistence\PdoEmailVerificationRepository;
use SwAuth\Infrastructure\Persistence\PdoMfaRepository;
use SwAuth\Infrastructure\Persistence\PdoPasswordResetRepository;
use SwAuth\Infrastructure\Persistence\PdoRoleRepository;
use SwAuth\Infrastructure\Persistence\PdoSessionRepository;
use SwAuth\Infrastructure\Persistence\PdoUserRepository;
use SwAuth\Shared\SecretEncryptor;

final class SwAuthContainer
{
    private RoleRepositoryInterface $roleRepo;
    private UserRepositoryInterface $userRepo;
    private SessionRepositoryInterface $sessionRepo;
    private MfaMethodRepositoryInterface $mfaRepo;
    private PasswordResetRepositoryInterface $passwordResetRepo;
    private EmailVerificationRepositoryInterface $emailVerificationRepo;

    private LoginService $loginService;
    private MfaService $mfaService;
    private ChangePasswordService $changePasswordService;
    private PasswordResetService $passwordResetService;
    private EmailVerificationService $emailVerificationService;
    private RegistrationService $registrationService;
    private SessionService $sessionService;

    public function __construct(PDO $pdo)
    {
        // 1. Repositórios — a única camada que conhece o PDO.
        // PdoUserRepository depende de RoleRepositoryInterface, por isso o Role tem de nascer primeiro.
        $this->roleRepo = new PdoRoleRepository($pdo);
        $this->userRepo = new PdoUserRepository($pdo, $this->roleRepo);
        $this->sessionRepo = new PdoSessionRepository($pdo);
        $this->mfaRepo = new PdoMfaRepository($pdo);
        $this->passwordResetRepo = new PdoPasswordResetRepository($pdo);
        $this->emailVerificationRepo = new PdoEmailVerificationRepository($pdo);

        // 2. Configuração lida do ambiente (já carregado por SwAuth::boot())
        $issuer = $_ENV['MFA_ISSUER'] ?? getenv('MFA_ISSUER') ?: 'SwAuth';
        $encryptionKey = $_ENV['MFA_ENCRYPTION_KEY'] ?? getenv('MFA_ENCRYPTION_KEY') ?: null;
        $sessionDurationHours = (int) ($_ENV['SESSION_DURATION_HOURS'] ?? getenv('SESSION_DURATION_HOURS') ?: 720);

        if ($encryptionKey === null || $encryptionKey === false || $encryptionKey === '') {
            throw new RuntimeException('A variável de ambiente MFA_ENCRYPTION_KEY não está definida.');
        }

        $encryptor = new SecretEncryptor($encryptionKey);

        // 3. Services — pela ordem das dependências.
        $this->mfaService = new MfaService($this->mfaRepo, $encryptor, $issuer);

        $lockoutPolicy = new AccountLockoutPolicy();

        $this->loginService = new LoginService(
            userRepositoryInterface: $this->userRepo,
            lockoutPolicy: $lockoutPolicy,
            mfaMethodRepositoryInterface: $this->mfaRepo,
            mfaService: $this->mfaService,
        );

        $this->changePasswordService = new ChangePasswordService(
            userRepositoryInterface: $this->userRepo,
        );

        $this->passwordResetService = new PasswordResetService(
            userRepositoryInterface: $this->userRepo,
            passwordResetRepositoryInterface: $this->passwordResetRepo,
        );

        $this->emailVerificationService = new EmailVerificationService(
            userRepositoryInterface: $this->userRepo,
            emailVerificationRepositoryInterface: $this->emailVerificationRepo,
        );

        $this->registrationService = new RegistrationService(
            userRepositoryInterface: $this->userRepo,
            roleRepositoryInterface: $this->roleRepo,
        );

        $this->sessionService = new SessionService(
            sessionRepo: $this->sessionRepo,
            sessionDuration: $sessionDurationHours,
        );
    }

    public function login(): LoginService
    {
        return $this->loginService;
    }

    public function mfa(): MfaService
    {
        return $this->mfaService;
    }

    public function changePassword(): ChangePasswordService
    {
        return $this->changePasswordService;
    }

    public function passwordReset(): PasswordResetService
    {
        return $this->passwordResetService;
    }

    public function emailVerification(): EmailVerificationService
    {
        return $this->emailVerificationService;
    }

    public function registration(): RegistrationService
    {
        return $this->registrationService;
    }

    public function session(): SessionService
    {
        return $this->sessionService;
    }
}
