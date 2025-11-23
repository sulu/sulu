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

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SystemListener implements EventSubscriberInterface
{
    public function __construct(
        private SystemStoreInterface $systemStore,
        private string $context
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 24]];
    }

    public function onKernelRequest(RequestEvent $requestEvent)
    {
        if (SuluKernel::CONTEXT_ADMIN === $this->context) {
            $this->systemStore->setSystem(Admin::SULU_ADMIN_SECURITY_SYSTEM);

            return;
        }
    }
}
