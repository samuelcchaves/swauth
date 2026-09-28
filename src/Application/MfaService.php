<?php 


namespace SwAuth\Application;

use OTPHP\TOTP;

use SwAuth\Domain\Entities\MfaMethod;
use SwAuth\Domain\Repositories\MfaMethodRepositoryInterface;
use SwAuth\Domain\ValueObjects\BackupCode;
use SwAuth\Shared\SecretEncryptor;
use SwAuth\Domain\ValueObjects\Email;

final class MfaService {

    private MfaMethodRepositoryInterface $mfaRepo;
    private SecretEncryptor $encryptor;
    private string $issuer;

    public function __construct(MfaMethodRepositoryInterface $mfaRepo, SecretEncryptor $encryptor, string $issuer){
        $this->mfaRepo = $mfaRepo;
        $this->encryptor = $encryptor;
        $this->issuer = $issuer;
    }

    public function setup(int $userId, Email $email): array {
        $findMethodByUserId = $this->mfaRepo->findActiveByUserId($userId);


        if($findMethodByUserId){
            $this->mfaRepo->deactivate($findMethodByUserId->getId());
        }
    
        $totp = TOTP::create();
        $totp->setLabel($email);
        $totp->setIssuer($this->issuer);
        $secretPlain = $totp->getSecret();

        $mfaMethod = new MfaMethod(id: 0, userId: $userId, type: 'totp', secretEncrypted: $this->encryptor->encrypt($secretPlain),isActive: false, lastUsedAt: null);

        $created = $this->mfaRepo->insert($mfaMethod);

        return ['mfaMethodId' =>$created->getId(), 'provisioningUri' => $totp->getProvisioningUri()];
    }

    public function confirm(int $mfaMethodId, string $code): bool { 
        $mfaMethod = $this->mfaRepo->findById($mfaMethodId);

        if($mfaMethod === null) {
            return false;
        }

        $secretPlain = $this->encryptor->decrypt($mfaMethod->getSecretEncrypted());
        $totp = TOTP::createFromSecret($secretPlain);

        if(!$totp->verify($code)) {
            return false;
        }

        $this->mfaRepo->activate($mfaMethodId);

        return true;
    }

    public function verify(int $userId, string $code): bool {
        $mfaMethod = $this->mfaRepo->findActiveByUserId($userId);

        if($mfaMethod === null) {
            return false;
        }   

        $secretPlain = $this->encryptor->decrypt($mfaMethod->getSecretEncrypted());
        $totp = TOTP::createFromSecret($secretPlain);

        if($totp->verify($code)) {
            $mfaMethod->recordUsage();
            $this->mfaRepo->update($mfaMethod);
            return true;
        }

        if($this->mfaRepo->consumeBackupCode($userId, BackupCode::fromRaw($code))) {
            $mfaMethod->recordUsage();
            $this->mfaRepo->update($mfaMethod);
            return true;
        }

        return false;
    }

    public function generateBackupCodes(int $userId, int $count = 10): array {
        $rawCodes = [];
        $hashes = [];
        
        for($i = 0; $i < $count; $i++) {
            $pair = BackupCode::generate();
            $rawCodes[] = $pair['raw'];
            $hashes[] = $pair['hash'];
        }

        $this->mfaRepo->saveBackupCode($userId, $hashes);

        return $rawCodes;
    }

    public function disable(int $userId): void {
        $mfaMethod = $this->mfaRepo->findActiveByUserId($userId);

        if($mfaMethod === null) {
            return;
        }

        $this->mfaRepo->deactivate($mfaMethod->getId());
    }



    
}