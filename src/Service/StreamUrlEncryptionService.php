<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StreamUrlEncryptionService
{
    private string $encryptionKey;
    private string $cipherMethod = 'aes-256-gcm';

    public function __construct(ParameterBagInterface $parameterBag)
    {
        // Utiliser une clé d'encryption basée sur APP_SECRET
        $this->encryptionKey = hash('sha256', $parameterBag->get('app_secret'), true);
    }

    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(16); // GCM recommande 96 bits (12 bytes) mais nous utilisons 128 bits
        $tag = '';

        $encrypted = openssl_encrypt(
            $plainText,
            $this->cipherMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Combiner IV + TAG + données chiffrées
        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $encryptedText): string
    {
        $data = base64_decode($encryptedText);

        if ($data === false) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipherMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }
}