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

use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Uid\Uuid;

/**
 * This listener ensures, that requests with invalid usernames have the same response time as valid users.
 *
 * @internal no backwards compatibility promise given for this class it can be removed or changed at any time
 */
class AuthenticationFailureListener implements EventSubscriberInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents()
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $previousException = $event->getException()->getPrevious();

        if ($previousException instanceof UserNotFoundException) {
            $user = $this->userRepository->createNew();

            $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $hasher->hash(Uuid::v7()->toRfc4122());
        }
    }
}
