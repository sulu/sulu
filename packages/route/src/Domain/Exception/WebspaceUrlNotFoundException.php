<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Exception;

/**
 * Thrown by a route generator when no URL can be built for a slug in a locale and site.
 */
class WebspaceUrlNotFoundException extends \RuntimeException
{
    public function __construct(string $slug, string $locale, string $site, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('No url found for "%s" in locale "%s" and site "%s".', $slug, $locale, $site),
            $code,
            $previous,
        );
    }
}
