<?php

namespace Helpers;

class CryptoHelper
{
    // secret key (should be 32 bytes for AES-256)
    private static $secretKey;

    public static function getSecretKey()
    {
        if (!self::$secretKey) {
            self::$secretKey = $_ENV['ENC_SECRET_KEY'] ?? '';
        }
        return self::$secretKey;
    }

    // Encrypt data using AES-256-CBC and return base64 string (for Angular compatibility)
    public static function encrypt($plaintext, $iv)
    {
        $key = self::getSecretKey();
        $cipher = 'AES-256-CBC';
        $encrypted = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted);
    }

    // Decrypt data using AES-256-CBC (accepts base64 input from Angular)
    public static function decrypt($ciphertext, $iv)
    {
        $key = self::getSecretKey();
        $cipher = 'AES-256-CBC';
        $decodedCiphertext = base64_decode($ciphertext);
        $decrypted = openssl_decrypt($decodedCiphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    // Generate a random IV (16 bytes for AES-256-CBC)
    public static function generateIv()
    {
        return openssl_random_pseudo_bytes(16);
    }
}
