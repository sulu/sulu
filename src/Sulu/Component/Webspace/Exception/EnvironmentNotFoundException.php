<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Exception;

use Sulu\Component\Webspace\Portal;

/**
 * This exception is thrown when an environment in a portal does not exist.
 */
class EnvironmentNotFoundException extends PortalException
{
    /**
     * @var string
     */
    private $environment;

    public function __construct(Portal $portal, $environment)
    {
        parent::__construct(
            sprintf('The environment "%s" could not be found in the portal "%s".', $environment, $portal->getKey())
        );

        $this->portal = $portal;
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
}
