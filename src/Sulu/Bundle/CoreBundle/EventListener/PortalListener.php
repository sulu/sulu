<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\EventListener;

use Sulu\Component\Workspace\Manager\PortalManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * This listener sets the current portal on the portal manager
 * @package Sulu\Bundle\CoreBundle\EventListener
 */
class PortalListener
{
    /**
     * @var \Sulu\Component\Workspace\Manager\PortalManagerInterface
     */
    protected $portalManager;

    public function __construct(PortalManagerInterface $portalManager)
    {
        $this->portalManager = $portalManager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Get the current portal and set it in portal manager
        $portal = $this->portalManager->findByUrl($event->getRequest()->getHost());
        $this->portalManager->setCurrentPortal($portal);
    }
}
