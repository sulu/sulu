<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
    /**
     * @var HasherInterface
     */
    private $hasher;

    /**
     * @var string
     */
    private $hashParameter;

    /**
     * @var string
     */
    private $forceParameter;

    public function __construct(HasherInterface $hasher, $hashParameter = '_hash', $forceParameter = 'force')
    {
        $this->hasher = $hasher;
        $this->hashParameter = $hashParameter;
        $this->forceParameter = $forceParameter;
    }

    /**
     * {@inheritdoc}
     */
    public function checkHash(Request $request, $object, $identifier)
    {
        if (!$request->request->has($this->hashParameter)
            || $request->query->get('force', false) === 'true'
            || $request->request->get($this->hashParameter) == $this->hasher->hash($object)
        ) {
            return true;
        }

        throw new InvalidHashException(get_class($object), $identifier);
    }
}
