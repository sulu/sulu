<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

/**
 * Provide "encodeAlias" function for doctrine-queries.
 */
trait EncodeAliasTrait
{
    /**
     * @template T of array<string>|string
     *
     * @param T|null $value
     *
     * @return T
     */
    protected function encodeAlias($value)
    {
        if (null === $value) {
            return '';
        }

        /** @var T */
        return \preg_replace_callback(
            '/(?:"[^"]+")|([\\\])|(?<=\S)(:)/',
            function($matches) {
                if (false !== \strpos($matches[0], '"')) {
                    return $matches[0];
                }

                return '_';
            },
            $value
        );
    }
}
