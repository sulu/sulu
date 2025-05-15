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
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * This listener ensures, that requests with invalid usernames have the same response time as valid users.
 */
class AuhenticationFailureListener implements EventSubscriberInterface
{
    /**
     * @param PasswordHasherFactoryInterface|EncoderFactoryInterface $passwordHasherFactory
     */
    public function __construct(private $passwordHasherFactory, private UserRepositoryInterface $userRepository)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            class_exists(AuthenticationFailureEvent::class) ? AuthenticationFailureEvent::class : LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(AuthenticationFailureEvent|LoginFailureEvent $event)
    {
        $exception = $event instanceof AuthenticationFailureEvent ? $event->getAuthenticationException() : $event->getException();
        $previousException = $exception->getPrevious();
        if ($previousException instanceof UsernameNotFoundException || $previousException instanceof UserNotFoundException) {
            $user = $this->userRepository->createNew();

            if ($this->passwordHasherFactory instanceof PasswordHasherFactoryInterface) {
                $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
                $hasher->hash(Uuid::uuid4()->toString());
            } else {
                $encoder = $this->passwordHasherFactory->getEncoder($user);
                $encoder->encodePassword(Uuid::uuid4()->toString(), 'dummy-salt');
            }
        }
    }
}
