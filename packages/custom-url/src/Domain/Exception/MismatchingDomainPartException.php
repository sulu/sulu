<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Domain\Exception;

use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a missing domain-part is detected.
 */
class MismatchingDomainPartException extends RestException
{
    /**
     * @param array<string> $domainParts
     */
    public function __construct(
        private string $baseDomain,
        private array $domainParts,
    ) {
        parent::__construct(
            \sprintf('Domain-part mismatch "%s" with placeholders: %s', $baseDomain, \implode(', ', $domainParts)),
            9003
        );
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    /**
     * @return array<string>
     */
    public function getDomainParts(): array
    {
        return $this->domainParts;
    }
}
