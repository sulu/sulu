<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * This listener ensures, that requests with invalid usernames have the same response time as valid users.
 */
class AuthenticationFailureListener implements EventSubscriberInterface
{
    /**
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     */
    public function __construct(private $passwordHasherFactory, private UserRepositoryInterface $userRepository)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event)
    {
        $previousException = $event->getException()->getPrevious();
        if ($previousException instanceof UserNotFoundException) {
            $user = $this->userRepository->createNew();

            $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $hasher->hash(Uuid::uuid4()->toString());
        }
    }
}
