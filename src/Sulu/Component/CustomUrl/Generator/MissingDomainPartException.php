<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var string
     */
    private $baseDomain;

    /**
     * @var array
     */
    private $domainParts;

    /**
     * @var string
     */
    private $domain;

    public function __construct($baseDomain, array $domainParts, $domain)
    {
        parent::__construct(
            sprintf('Missing domain-part for base-domain "%s" detected. Result domain: "%s"', $baseDomain, $domain),
            9003
        );

        $this->baseDomain = $baseDomain;
        $this->domainParts = $domainParts;
        $this->domain = $domain;
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
