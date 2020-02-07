<?php

namespace Sowe\Framework;

abstract class Keychain{
    private static $PK = "";
    private static $SEPARATOR = "|||";

    /**
     * Data packing and unpacking
     */
    private static function pack($data)
    {
        return implode(self::$SEPARATOR, $data);
    }

    private static function unpack($data)
    {
        return explode(self::$SEPARATOR, $data);
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
            }else{
                throw new \Exception("Undefined private key. Define it using Keychain::set_pk or the constant KEYCHAIN_PK");
            }
        }
    }
}