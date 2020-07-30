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
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * This listener ensures, that requests with invalid usernames have the same response time as valid users.
 */
class AuhenticationFailureListener implements EventSubscriberInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, UserRepositoryInterface $userRepository)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticationFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(AuthenticationFailureEvent $event)
    {
        $previousException = $event->getAuthenticationException()->getPrevious();
        if ($previousException instanceof UsernameNotFoundException) {
            $this->passwordEncoder->encodePassword($this->userRepository->createNew(), Uuid::uuid4()->toString());
        }
    }
}
