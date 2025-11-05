<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Security;

use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PageSecurityListener implements EventSubscriberInterface
{
    public function __construct(
        private ?SecurityCheckerInterface $securityChecker = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 7], // set the security listener after the firewall and after the routing listener
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (null === $this->securityChecker) {
            return;
        }

        $requestAttributes = $request->attributes->get('_sulu');
        if (!$requestAttributes instanceof RequestAttributes) {
            return;
        }

        $object = $request->attributes->get('object');
        if (!$object instanceof PageDimensionContentInterface) {
            return;
        }

        $webspace = $requestAttributes->getAttribute('webspace');
        if (!$webspace instanceof Webspace || !$webspace->hasWebsiteSecurity()) {
            return;
        }

        $page = $object->getResource();
        $locale = $object->getLocale();

        $this->securityChecker->checkPermission(
            new SecurityCondition(
                'sulu.webspaces.' . $page->getWebspaceKey(),
                $locale,
                Page::class,
                $page->getUuid()
            ),
            PermissionTypes::VIEW
        );
    }
}
