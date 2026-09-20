<?php 


namespace SwAuth\Domain\ValueObjects;

final class BackupCode {

    private string $hash;

    private function __construct(string $hash) {
        $this->hash = $hash;        
    }

    
    public static function generate(): array {
        $raw = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        return [
            'raw' => $raw,
            'hash' => new self(hash('sha256', $raw)),
        ];
    }

    public static function fromRaw(string $raw): self
    {
        return new self(hash('sha256', $raw));

    }

    public function matches(string $raw): bool{
        return hash_equals($this->hash, hash('sha256', $raw));
    }

    public function __toString(): string
    {
        return $this->hash;
    }

}