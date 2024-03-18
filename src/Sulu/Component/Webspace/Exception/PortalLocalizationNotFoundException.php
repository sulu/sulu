<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

use Sulu\Component\Webspace\Portal;

/**
 * This exception is thrown when an environment in a portal does not exist.
 */
class PortalLocalizationNotFoundException extends PortalException
{
    public function __construct(
        Portal $portal,
        private string $locale
    ) {
        parent::__construct(
            \sprintf('The locale "%s" could not be found in the portal "%s".', $locale, $portal->getKey())
        );

        $this->portal = $portal;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
