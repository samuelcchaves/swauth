<?php

require __DIR__ . '/../vendor/autoload.php';

use Src\Infrastructure\Persistence\PdoEmailVerificationRepository as PersistencePdoEmailVerificationRepository;
use SwAuth\Domain\ValueObjects\HashedPassword;
use SwAuth\Domain\ValueObjects\TokenHash;
use SwAuth\Infrastructure\Persistence\Database;
use SwAuth\Infrastructure\Persistence\PdoRoleRepository;
use SwAuth\Infrastructure\Persistence\PdoUserRepository;
use SwAuth\Infrastructure\Persistence\PdoSessionRepository;
use SwAuth\Infrastructure\Persistence\PdoMfaRepository;
use SwAuth\Infrastructure\Persistence\PdoPasswordResetRepository;



use SwAuth\Domain\Entities\User;
use SwAuth\Domain\Entities\Session;
use SwAuth\Domain\ValueObjects\BackupCode;
use SwAuth\Domain\ValueObjects\Email;
use SwAuth\Domain\Entities\PasswordResetToken;
use SwAuth\Domain\Entities\EmailVerificationToken;

SwAuth\SwAuth::boot(__DIR__ . '/..');



$pdo = Database::getInstance();
$roleRepo = new PdoRoleRepository($pdo);

/* echo "== findAll == \n";
$allRoles = $roleRepo->findAll();
foreach ($allRoles as $role) {
    echo "Found Role {$role->getRoleName()} with level {$role->getLevel()}\n";
}

echo "\n == findById == \n";
$roleById = $roleRepo->findById(999);
var_dump($roleById);


echo "\n == findByName == \n";
$roleByName = $roleRepo->findByName("admin");
var_dump($roleByName);

$roleByName = $roleRepo->findByName("inexistente");
var_dump($roleByName); */

$role = $roleRepo->findByName('admin');
$email = new Email("samuelcchaves1@gmail.com");
$pwd = HashedPassword::fromPlainText("123456");
$newUser = User::register($role, "samuelcchaves1", $email, $pwd);
$userRepo = new PdoUserRepository($pdo, $roleRepo);
$savedUser = $userRepo->insert($newUser);
$mfaRepo = new PdoMfaRepository($pdo);

echo "User criado com id: " . $savedUser->getId() . "\n";
echo "Username: " . $savedUser->getUsername() . "\n";
echo "Email: " . $savedUser->getEmail() . "\n";
echo "Role: " . $savedUser->getRole()->getRoleName() . "\n";


echo "Antes do update — email verificado: " . ($savedUser->isEmailVerified() ? 'sim' : 'não') . "\n";

$savedUser->verifyEmail();
$userRepo->update($savedUser);

$reloadedUser = $userRepo->findById($savedUser->getId());

echo "Depois do update — email verificado: " . ($reloadedUser->isEmailVerified() ? 'sim' : 'não') . "\n";


$reloadedUser->recordFailedLogin();
$userRepo->update($reloadedUser);

$afterFailure = $userRepo->findById($reloadedUser->getId());
echo "Failed login count: " . $afterFailure->getFailedLoginCount() . "\n";

echo HashedPassword::fromHash('$argon2id$v=19$m=65536,t=4,p=1$RHBXT2dyM1kudXZLeFBlVw$tArddu9WcP9aPOVqZOooZiqQ1g6vl1wlez70Zwb89EU')->verify("123456") ? "sim\n" : "não\n";


$tokenPair = TokenHash::generate();
$sessionRepo = new PdoSessionRepository($pdo);
$newSession = new Session(
    id: 0,
    userId: $savedUser->getId(),
    tokenHash: $tokenPair['hash'],
    userAgent: 'PHPUnit Test Agent',
    ipCreated: '127.0.0.1',
    expiresAt: new DateTimeImmutable('+1 hour'),
    revokedAt: null
);

$savedSession = $sessionRepo->insert($newSession);
echo "Sessão criada com id: " . $savedSession->getId() . "\n";

$found = $sessionRepo->findByTokenHash($tokenPair['hash']);
echo "Encontrada: " . ($found !== null ? 'sim' : 'não') . "\n";
echo "Válida: " . ($found->isValid() ? 'sim' : 'não') . "\n";

$found->revoke();
$sessionRepo->update($found);

$reloaded = $sessionRepo->findByTokenHash($tokenPair['hash']);
echo "Depois de revogar, válida: " . ($reloaded->isValid() ? 'sim' : 'não') . "\n";

$plainCodes = [];
$codeHashObjects = [];

for($i = 0; $i < 8; $i++){
    ['raw' => $raw, 'hash' => $hash] = BackupCode::generate();
    $plainCodes[] = $raw;
    $codeHashObjects[] = $hash;
}

$mfaRepo->saveBackupCode($savedUser->getId(), $codeHashObjects);

echo "Códigos gerados (mostrar uma vez só): \n";

foreach($plainCodes as $code) {
    echo $code . "\n";
}


$digitedByUser = $plainCodes[0];
$toCheck = BackupCode::fromRaw($digitedByUser);

$consumed = $mfaRepo->consumeBackupCode($savedUser->getId(), $toCheck);
echo "Consumido: " . ($consumed ? 'sim' : 'não') . "\n";
echo "Por usar depois: " . $mfaRepo->countUnusedBackupCodes($savedUser->getId()) . "\n";

// Tenta consumir outra vez o mesmo — deve falhar
$consumedAgain = $mfaRepo->consumeBackupCode($savedUser->getId(), $toCheck);
echo "Consumido segunda vez (deve ser não): " . ($consumedAgain ? 'sim' : 'não') . "\n";


$passwordResetRepo = new PdoPasswordResetRepository($pdo);

// 1. Criar o primeiro pedido de reset
$tokenPair1 = TokenHash::generate();
$firstToken = new PasswordResetToken(
    id: 0,
    userId: $savedUser->getId(),
    tokenHash: $tokenPair1['hash'],
    status: 'pending',
    expiresAt: new DateTimeImmutable('+1 hour')
);
$savedFirstToken = $passwordResetRepo->insert($firstToken);
echo "1º token criado com id: " . $savedFirstToken->getId() . "\n";

// 2. Criar um segundo pedido — isto deve invalidar o primeiro
$passwordResetRepo->supersedePendingForUser($savedUser->getId());

$tokenPair2 = TokenHash::generate();
$secondToken = new PasswordResetToken(
    id: 0,
    userId: $savedUser->getId(),
    tokenHash: $tokenPair2['hash'],
    status: 'pending',
    expiresAt: new DateTimeImmutable('+1 hour')
);
$savedSecondToken = $passwordResetRepo->insert($secondToken);
echo "2º token criado com id: " . $savedSecondToken->getId() . "\n";

// 3. Confirmar que o primeiro já não é encontrado como pending (foi superseded)
$foundFirst = $passwordResetRepo->findPendingByTokenHash($tokenPair1['hash']);
echo "1º token ainda pending: " . ($foundFirst !== null ? 'sim (ERRO)' : 'não (correto)') . "\n";

// 4. Confirmar que o segundo continua pending
$foundSecond = $passwordResetRepo->findPendingByTokenHash($tokenPair2['hash']);
echo "2º token pending: " . ($foundSecond !== null ? 'sim (correto)' : 'não (ERRO)') . "\n";

// 5. Consumir o segundo token (simula reset de password bem-sucedido)
$foundSecond->markAsUsed();
$passwordResetRepo->update($foundSecond);

$afterUse = $passwordResetRepo->findPendingByTokenHash($tokenPair2['hash']);
echo "2º token pending depois de usado: " . ($afterUse !== null ? 'sim (ERRO)' : 'não (correto)') . "\n";

// EMAIL TEST


$emailResetToken1 = TokenHash::generate();
$firstToken = new EmailVerificationToken(
    id: 0,
    userId: $savedUser->getId(),
    tokenHash: $tokenPair1['hash'],
    status: 'pending',
    expiresAt: new DateTimeImmutable('+24 hour')
);


