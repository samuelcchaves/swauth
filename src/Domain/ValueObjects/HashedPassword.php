<?php

namespace SwAuth\Domain\ValueObjects;

final class HashedPassword 
{
    private string $password;
    private function __construct(string $hashedValue) {
        $this->password = $hashedValue;
    }
    
    public static function fromHash(string $hash): self {
        return new self($hash);
    }

    public static function fromPlainText(string $plainPassword): self {
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID); 
    
        return new self($hash);
    }

    public function verify(string $plainPassword): bool {
        return password_verify($plainPassword, $this->password);
    }

    public function needsRehash(): bool {
        return password_needs_rehash($this->password, PASSWORD_ARGON2ID);
    }

    public function __toString(): string {
        return $this->password;
    }
    



}
