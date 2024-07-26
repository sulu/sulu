<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Generator;

use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a missing domain-part is detected.
 */
class MissingDomainPartException extends RestException
{
    /**
     * @param string $baseDomain
     * @param string $domain
     */
    public function __construct(
        private $baseDomain,
        private array $domainParts,
        private $domain,
    ) {
        parent::__construct(
            \sprintf('Missing domain-part for base-domain "%s" detected. Result domain: "%s"', $baseDomain, $domain),
            9003
        );
    }

    /**
     * @return string
     */
    public function getBaseDomain()
    {
        return $this->baseDomain;
    }

    /**
     * @return array
     */
    public function getDomainParts()
    {
        return $this->domainParts;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
