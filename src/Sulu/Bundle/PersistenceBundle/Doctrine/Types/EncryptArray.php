<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Doctrine\Types;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * @internal
 */
final class EncryptArray extends TextType
{
    private static ?Key $key = null;

    private static ?string $encryptionKey = null;

    /**
     * Need to be set for security reasons no getter.
     */
    public static function setEncryptionKey(?string $encryptionKey): void
    {
        self::$encryptionKey = $encryptionKey;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!\is_array($value)) {
            return $value;
        }

        $value = \json_encode($value, \JSON_THROW_ON_ERROR);

        if (self::$encryptionKey) {
            $value = Crypto::encrypt($value, self::getKey());
        }

        return $value;
    }

    public function convertToPHPValue($value, $platform): ?array
    {
        if (!\is_string($value)) {
            return $value;
        }

        if (self::$encryptionKey) {
            $value = Crypto::decrypt($value, self::getKey());
        }

        return \json_decode($value, true, \JSON_THROW_ON_ERROR);
    }

    private static function getKey(): Key
    {
        if (null === self::$key) {
            self::$key = Key::loadFromAsciiSafeString(self::$encryptionKey);
        }

        return self::$key;
    }
}
