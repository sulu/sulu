<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\User;

use Doctrine\ORM\NoResultException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Responsible for loading the user from the database for the Symfony security system. Takes also the security system
 * configuration from the webspaces into account.
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $suluSystem;

    public function __construct(UserRepositoryInterface $userRepository, RequestStack $requestStack, $suluSystem)
    {
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
        $this->suluSystem = $suluSystem;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $exceptionMessage = sprintf(
            'Unable to find an Sulu\Component\Security\Authentication\UserInterface object identified by %s',
            $username
        );

        try {
            $user = $this->userRepository->findUserByIdentifier($username);

            if (!$user->getEnabled()) {
                throw new DisabledException();
            }

            if ($user->getLocked()) {
                throw new LockedException();
            }

            foreach ($user->getRoleObjects() as $role) {
                if ($role->getSystem() === $this->getSystem()) {
                    return $user;
                }
            }
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException($exceptionMessage, 0, $e);
        }

        throw new UsernameNotFoundException($exceptionMessage, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(BaseUserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instance of "%s" are not supported.',
                    $class
                )
            );
        }

        $user = $this->userRepository->findUserWithSecurityById($user->getId());

        if (!$user->getEnabled()) {
            throw new DisabledException();
        }

        if ($user->getLocked()) {
            throw new LockedException();
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_subclass_of($class, UserInterface::class);
    }

    /**
     * Returns the required system for the current request.
     *
     * @return string
     */
    private function getSystem()
    {
        $system = $this->suluSystem;
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request
            && $request->attributes->has('_sulu')
            && null !== ($webspace = $request->attributes->get('_sulu')->getAttribute('webspace'))
            && null !== ($security = $webspace->getSecurity())
        ) {
            $system = $security->getSystem();
        }

        return $system;
    }
}
