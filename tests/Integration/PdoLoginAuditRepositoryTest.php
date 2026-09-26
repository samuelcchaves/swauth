<?php

declare(strict_types=1);

namespace SwAuth\Tests\Integration;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use SwAuth\Infrastructure\Persistence\Database;
use SwAuth\Infrastructure\Persistence\PdoLoginAuditRepository;
use SwAuth\Infrastructure\Persistence\PdoRoleRepository;
use SwAuth\Infrastructure\Persistence\PdoUserRepository;
use SwAuth\Domain\Entities\User;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\ValueObjects\HashedPassword;


class PdoLoginAuditRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoLoginAuditRepository $repository;
    private int $userId1;
    private int $userId2;


    protected function setUp(): void
    {
        $this->pdo = Database::getInstance();

        $roleRepo = new PdoRoleRepository($this->pdo);
        $userRepo = new PdoUserRepository($this->pdo, $roleRepo);
        /* $userRole = $roleRepo->findById(10); */

        $userRole = $roleRepo->findById(10);
        $userEmail1 = new Email('samuelcchave1@email.com');
        $userPassword1 = HashedPassword::fromPlainText('123456');
        
        $userEmail2 = new Email('marcosaurelio@email.com');
        $userPassword2 = HashedPassword::fromPlainText('654321');
    
        $user1 = User::register($userRole, 'samuelcchaves', $userEmail1, $userPassword1, 'Samuel', 'Chaves');
        $user2 = User::register($userRole, 'marcosaurelio', $userEmail2, $userPassword2, 'Marcos', 'Aurelio');

        $userObj1 = $userRepo->insert($user1);
        $userObj2 = $userRepo->insert($user2);

        $this->userId1 = $userObj1->getId();
        $this->userId2 = $userObj2->getId();

        
        $this->repository = new PdoLoginAuditRepository($this->pdo);
        


        $this->pdo->exec('DELETE FROM login_audit');
    }

    protected function tearDown(): void
    {   
        $this->pdo->exec('DELETE FROM users');
        $this->pdo->exec('DELETE FROM login_audit');
        
    }

    public function testRecordInsertsFailedAttempt(): void
    {
        $this->repository->record(
            null,
            'naoexiste@teste.com',
            '127.0.0.1',
            false,
            'invalid_credentials'
        );

        $count = $this->repository->countRecentFailuresByIp(
            '127.0.0.1',
            new DateTimeImmutable('-1 minute')
        );

        $this->assertSame(1, $count);
    }

    public function testRecordInsertsSuccessfulAttemptAndDoesNotCountAsFailure(): void
    {
        $this->repository->record(
            $this->userId1,
            'user@teste.com',
            '10.0.0.5',
            true,
            null
        );

        $count = $this->repository->countRecentFailuresByUser(
            $this->userId1,
            new DateTimeImmutable('-1 minute')
        );

        $this->assertSame(0, $count);
    }

    public function testCountRecentFailuresByUserCountsOnlyThatUser(): void
    {
        $this->repository->record($this->userId1, 'a@teste.com', '10.0.0.1', false, 'invalid_credentials');
        $this->repository->record($this->userId1, 'a@teste.com', '10.0.0.1', false, 'invalid_credentials');
        $this->repository->record($this->userId2, 'b@teste.com', '10.0.0.2', false, 'invalid_credentials');

        $countUser1 = $this->repository->countRecentFailuresByUser($this->userId1, new DateTimeImmutable('-1 minute'));
        $countUser2 = $this->repository->countRecentFailuresByUser($this->userId2, new DateTimeImmutable('-1 minute'));

        $this->assertSame(2, $countUser1);
        $this->assertSame(1, $countUser2);
    }

}