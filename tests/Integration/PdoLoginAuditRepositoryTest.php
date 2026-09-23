<?php

declare(strict_types=1);

namespace SwAuth\Tests\Integration;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use SwAuth\Infrastructure\Persistence\Database;
use SwAuth\Infrastructure\Persistence\PdoLoginAuditRepository;

class PdoLoginAuditRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoLoginAuditRepository $repository;


    // Setting up the elements needed for the test to run
    protected function setUp(): void
    {
        // get $pdo
        $this->pdo = Database::getInstance();

        // instantiate the repo and inject $pdo
        $this->repository = new PdoLoginAuditRepository($this->pdo);

        // empty the table to stat tests in a clean way
        $this->pdo->exec('DELETE FROM login_audit');
    }

    // Runs after the test ended
    protected function tearDown(): void
    {   
        // empty table 
        $this->pdo->exec('DELETE FROM login_audit');
    }

    public function testRecordInsertsFailedAttempt(): void
    {
        // Act
        $this->repository->record(
            null,
            'naoexiste@teste.com',
            '127.0.0.1',
            false,
            'invalid_credentials'
        );

        // Assert — sem findById(), confirmamos via count
        $count = $this->repository->countRecentFailuresByIp(
            '127.0.0.1',
            new DateTimeImmutable('-1 minute')
        );

        $this->assertSame(1, $count);
    }

    public function testRecordInsertsSuccessfulAttemptAndDoesNotCountAsFailure(): void
    {
        // Act
        $this->repository->record(
            1,
            'user@teste.com',
            '10.0.0.5',
            true,
            null
        );

        // Assert — sucesso não deve entrar na contagem de falhas
        $count = $this->repository->countRecentFailuresByUser(
            1,
            new DateTimeImmutable('-1 minute')
        );

        $this->assertSame(0, $count);
    }

    public function testCountRecentFailuresByUserCountsOnlyThatUser(): void
    {
        // Arrange
        $this->repository->record(1, 'a@teste.com', '10.0.0.1', false, 'invalid_credentials');
        $this->repository->record(1, 'a@teste.com', '10.0.0.1', false, 'invalid_credentials');
        $this->repository->record(2, 'b@teste.com', '10.0.0.2', false, 'invalid_credentials');

        // Act
        $countUser1 = $this->repository->countRecentFailuresByUser(1, new DateTimeImmutable('-1 minute'));
        $countUser2 = $this->repository->countRecentFailuresByUser(2, new DateTimeImmutable('-1 minute'));

        // Assert
        $this->assertSame(2, $countUser1);
        $this->assertSame(1, $countUser2);
    }

}