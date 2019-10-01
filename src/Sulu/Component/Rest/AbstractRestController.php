<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\Tests\Unit\RestControllerTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Abstract Controller for extracting some required rest functionality.
 */
abstract class AbstractRestController
{
    use RestControllerTrait;

    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->setViewHandler($viewHandler);

        $this->tokenStorage = $tokenStorage;
    }

    protected function getUser()
    {
        if (!$this->tokenStorage) {
            throw new \LogicException('The TokenStorage property was not set via the constructor".');
        }

        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
