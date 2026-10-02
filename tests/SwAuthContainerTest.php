<?php

namespace SwAuth\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SwAuth\Application\ChangePasswordService;
use SwAuth\Application\EmailVerificationService;
use SwAuth\Application\LoginService;
use SwAuth\Application\MfaService;
use SwAuth\Application\PasswordResetService;
use SwAuth\Application\RegistrationService;
use SwAuth\Application\SessionService;
use SwAuth\SwAuth;
use SwAuth\SwAuthContainer;

final class SwAuthContainerTest extends TestCase
{
    private PDO $pdo;

protected function setUp(): void
{
    parent::setUp();

    $this->pdo = $this->createMock(PDO::class);

    $_ENV['MFA_ISSUER'] = 'SwAuth Testing';
    $_ENV['MFA_ENCRYPTION_KEY'] = base64_encode(random_bytes(32));
    $_ENV['SESSION_DURATION_HOURS'] = '48';
}

    protected function tearDown(): void
    {
        unset($_ENV['MFA_ISSUER'], $_ENV['MFA_ENCRYPTION_KEY'], $_ENV['SESSION_DURATION_HOURS']);

        parent::tearDown();
    }

    public function testInitConstroiOContainerSemErros(): void
    {
        $container = SwAuth::init($this->pdo);

        $this->assertInstanceOf(SwAuthContainer::class, $container);
    }

    public function testCadaGetterDevolveOTipoCorreto(): void
    {
        $container = SwAuth::init($this->pdo);

        $this->assertInstanceOf(LoginService::class, $container->login());
        $this->assertInstanceOf(MfaService::class, $container->mfa());
        $this->assertInstanceOf(ChangePasswordService::class, $container->changePassword());
        $this->assertInstanceOf(PasswordResetService::class, $container->passwordReset());
        $this->assertInstanceOf(EmailVerificationService::class, $container->emailVerification());
        $this->assertInstanceOf(RegistrationService::class, $container->registration());
        $this->assertInstanceOf(SessionService::class, $container->session());
    }

    public function testGettersDevolvemSempreAMesmaInstancia(): void
    {
        $container = SwAuth::init($this->pdo);

        $this->assertSame($container->login(), $container->login());
        $this->assertSame($container->mfa(), $container->mfa());
        $this->assertSame($container->session(), $container->session());
    }

    public function testFalhaSeFaltarAChaveDeEncriptacaoDoMfa(): void
    {
        unset($_ENV['MFA_ENCRYPTION_KEY']);

        $this->expectException(RuntimeException::class);

        SwAuth::init($this->pdo);
    }

    public function testUsaODefaultDeSessaoQuandoNaoDefinidoNoEnv(): void
    {
        unset($_ENV['SESSION_DURATION_HOURS']);

        // Não deve rebentar — deve cair no fallback de 720h.
        $container = SwAuth::init($this->pdo);

        $this->assertInstanceOf(SessionService::class, $container->session());
    }
}