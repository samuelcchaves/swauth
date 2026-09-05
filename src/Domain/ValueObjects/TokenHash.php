<?php 


namespace SwAuth\Domain\ValueObjects;


final class TokenHash {
    private string $tokenHash;

    private function __construct(string $tokenHash){
        $this->tokenHash = $tokenHash;
    }

    public static function generate(): array {
        $raw = bin2hex(random_bytes(32));

        return  [
            "raw" => $raw,
            "hash" => new self(self::hashRawToken($raw)),
        ];
    }

    public static function hashRawToken(string $raw): string {
        return hash("sha256", $raw);
    }

    public static function fromStoredHash(string $hash): self {
        return new self ($hash);
    }

    public function matches(string $rawToken): bool {
        return hash_equals($this->tokenHash, self::hashRawToken($rawToken));
    }

    public function __toString(): string {
        return $this->tokenHash;
    }

    


}