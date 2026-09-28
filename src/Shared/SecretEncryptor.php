<?php 

declare(strict_types = 1);

namespace SwAuth\Shared;

use InvalidArgumentException;
use RuntimeException;

final class SecretEncryptor {
    private const KEY_LENGTH = SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
    private const NONCE_LENGTH = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

    private string $key;

    public function __construct(string $base64Key) {
        $key = base64_decode($base64Key, strict: true);

        if($key === false || strlen($key) !== self::KEY_LENGTH) {
            throw new InvalidArgumentException('A chave de encriptação deve ser uma string base64. válida de '. self::KEY_LENGTH. 'bytes.');
        }

        $this->key = $key;
    }

    public function encrypt(string $plainText): string {

        $nonce = random_bytes(self::NONCE_LENGTH);
        $chipertext = sodium_crypto_secretbox($plainText, $nonce, $this->key);

        $combined = $nonce . $chipertext; // Junta o salt com a cifra

        sodium_memzero($plainText);

        return base64_encode($combined);
    }

    public function decrypt(string $encoded): string {
        $combined = base64_decode($encoded, strict: true);

        if($combined === false || strlen($combined) < self::NONCE_LENGTH) {
            throw new RuntimeException('Valor encriptado inválido ou corrompido');
        }

        $nonce = substr($combined, 0, self::NONCE_LENGTH); // pega no salt pegando nos primeiros self::NONCE_LENGTH bytes
        $chipertext = substr($combined, self::NONCE_LENGTH); // pega em tudo o resto (cifra) a partir dos self::NONCE_LENGTH  bytes

        $plainText = sodium_crypto_secretbox_open($chipertext, $nonce, $this->key);

        if($plainText === false) {
            throw new RuntimeException('Falha ao desencriptador - corrompido, adulterado ou chave errada');
        }

        return $plainText;
    }

    public static function generateKey(): string {
        return base64_encode(random_bytes(self::KEY_LENGTH));
    }

}