<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash;

use Sulu\Component\Rest\Exception\InvalidHashException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks the request for the delivered hash.
 */
class RequestHashChecker implements RequestHashCheckerInterface
{
    public function __construct(
        private HasherInterface $hasher,
        private string $hashParameter = '_hash',
        private string $forceParameter = 'force'
    ) {
    }

    public function checkHash(Request $request, $object, $identifier)
    {
        if (!$request->request->has($this->hashParameter)
            || 'true' === $request->query->get($this->forceParameter, false)
            || $request->request->get($this->hashParameter) == $this->hasher->hash($object)
        ) {
            return true;
        }

        throw new InvalidHashException(\get_class($object), $identifier);
    }
}
