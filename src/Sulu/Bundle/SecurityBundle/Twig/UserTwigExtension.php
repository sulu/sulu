<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Twig;

use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle users in frontend.
 */
class UserTwigExtension extends AbstractExtension
{
    public function __construct(private ArrayAdapter $cache, private UserRepository $userRepository)
    {
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_user', $this->resolveUserFunction(...)),
        ];
    }

    /**
     * resolves user id to user data.
     *
     * @param mixed $id id to resolve
     *
     * @return UserInterface|null
     */
    public function resolveUserFunction($id)
    {
        if (!\is_int($id)) {
            return null;
        }

        return $this->cache->get((string) $id, function() use ($id) {
            return $this->userRepository->findUserById($id);
        });
    }
}
