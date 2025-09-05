<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityListener implements EventSubscriberInterface
{
    /**
     * @var SecurityCheckerInterface|null
     */
    private $securityChecker;

    public function __construct(
        ?SecurityCheckerInterface $securityChecker = null
    ) {
        $this->securityChecker = $securityChecker;
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

        $webspace = $requestAttributes->getAttribute('webspace');

        $structure = $request->attributes->get('structure');
        if (!$structure instanceof PageBridge) {
            return;
        }

        $document = $structure->getDocument();
        if (!$document instanceof BasePageDocument) {
            return;
        }

        if ($webspace->hasWebsiteSecurity()) {
            $this->securityChecker->checkPermission(
                new SecurityCondition(
                    'sulu.webspaces.' . $document->getWebspaceName(),
                    $document->getLocale(),
                    \get_class($document),
                    $document->getUuid()
                ),
                PermissionTypes::VIEW
            );
        }
    }
}
