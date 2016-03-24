<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Twig;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;

/**
 * Extension to handle users in frontend.
 */
class UserTwigExtension extends \Twig_Extension
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache, UserRepository $userRepository)
    {
        $this->cache = $cache;
        $this->userRepository = $userRepository;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_resolve_user', [$this, 'resolveUserFunction']),
        ];
    }

    /**
     * resolves user id to user data.
     *
     * @param int $id id to resolve
     *
     * @return User
     */
    public function resolveUserFunction($id)
    {
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }

        $user = $this->userRepository->findUserById($id);
        if ($user === null) {
            return;
        }

        $this->cache->save($id, $user);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_user';
    }
}
