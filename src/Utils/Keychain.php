<?php

namespace Sowe\Framework\Utils;

class Keychain
{
    private static $PK = "";
    private static $SEPARATOR = "|||";

    private static function pack($data)
    {
        return implode(self::$SEPARATOR, $data);
    }
    private static function unpack($data)
    {
        return explode(self::$SEPARATOR, $data);
    }
    private static function encode($data)
    {
        return sodium_bin2hex($data);
    }
    private static function decode($data)
    {
        return sodium_hex2bin($data);
    }
    private static function nonce()
    {
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    }

    /**
     * PK Management
     */
    public static function set_pk($pk)
    {
        self::$PK = $pk;
    }

    public static function pk_validation()
    {
        if (empty(self::$PK)) {
            if (defined("KEYCHAIN_PK") && !empty(KEYCHAIN_PK)) {
                self::$PK = KEYCHAIN_PK;
            } else {
                throw new \Exception("Undefined private key. Define it using Keychain::set_pk or defining the constant KEYCHAIN_PK");
            }
        }
    }

    /**
     * Data encrypt/decrypt
     */
    public static function encrypt(... $params)
    {
        self::pk_validation();

        $nonce = self::nonce();
        return self::encode($nonce . sodium_crypto_secretbox(
            self::pack($params),
            $nonce,
            self::decode(self::$PK)
        ));
    }

    public static function decrypt($hash)
    {
        self::pk_validation();

        $bytes = self::decode($hash);
        return self::unpack(sodium_crypto_secretbox_open(
            mb_substr($bytes, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit'),
            mb_substr($bytes, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit'),
            self::decode(self::$PK)
        ));
    }

    /**
     * Signature & Verify
     */
    public static function signature()
    {
        self::pk_validation();

        return sodium_crypto_pwhash_scryptsalsa208sha256_str(
            self::decode(self::$PK),
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE
        );
    }

    public static function signature_verify($hash)
    {
        self::pk_validation();

        return sodium_crypto_pwhash_scryptsalsa208sha256_str_verify(
            $hash,
            self::decode(self::$PK)
        );
    }

    /**
     * Hashing & Verify
     */
    public static function hash($password)
    {
        self::pk_validation();
        return self::encode(sodium_crypto_auth($password, self::decode(self::$PK)));
    }

    public static function hash_verify($hash, $password)
    {
        self::pk_validation();
        return sodium_crypto_auth_verify(self::decode($hash), $password, self::decode(self::$PK));
    }
}
