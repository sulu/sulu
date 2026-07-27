<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\TwoFactor;

/**
 * @internal
 */
class BackupCodeGenerator
{
    public const CODE_LENGTH = 8;

    private const CODE_CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Generates a set of unique plain text backup codes.
     *
     * @param positive-int $count
     *
     * @return string[]
     */
    public function generate(int $count = 10): array
    {
        /** @var string[] $codes */
        $codes = [];

        while (\count($codes) < $count) {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
                $code .= self::CODE_CHARACTERS[\random_int(0, \strlen(self::CODE_CHARACTERS) - 1)];
            }

            if (!\in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * Hashes a plain text backup code for storage.
     */
    public function hash(string $code): string
    {
        return \password_hash($code, \PASSWORD_BCRYPT);
    }
}
