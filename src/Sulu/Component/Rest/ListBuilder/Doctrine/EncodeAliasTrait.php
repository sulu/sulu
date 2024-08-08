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
     * @param array<string>|string|null $value
     *
     * @return array<string>|string
     */
    protected function encodeAlias($value)
    {
        if (null === $value) {
            return '';
        }

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
