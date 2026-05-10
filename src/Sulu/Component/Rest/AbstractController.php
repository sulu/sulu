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

use Sulu\Component\Serializer\SuluSerializerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractController
{
    use ControllerTrait;

    public function __construct(
        private SuluSerializerInterface $suluSerializer,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {}

    protected function getUser(): UserInterface|null
    {
        if (!$this->tokenStorage) {
            throw new \LogicException('The TokenStorage property was not set via the constructor".');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
