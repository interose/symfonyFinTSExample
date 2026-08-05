<?php

namespace App\Service;

class EncryptionService
{
    private string $cipher = 'aes-256-ctr';
    private string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = hash('sha256', $encryptionKey, true);
    }

    /**
     * @throws \Exception
     */
    public function encrypt(?string $plainText): ?string
    {
        if (null === $plainText) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $initVector = random_bytes($ivLength);

        $encryptedText = openssl_encrypt($plainText, $this->cipher, $this->encryptionKey, 0, $initVector);

        return base64_encode($initVector.$encryptedText);
    }

    public function decrypt(?string $cipherText): ?string
    {
        if (null === $cipherText) {
            return null;
        }

        $decoded = base64_decode($cipherText);
        $ivLength = openssl_cipher_iv_length($this->cipher);

        $initVector = substr($decoded, 0, $ivLength);
        $encryptedText = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt($encryptedText, $this->cipher, $this->encryptionKey, 0, $initVector);

        return false !== $decrypted ? $decrypted : null;
    }
}
