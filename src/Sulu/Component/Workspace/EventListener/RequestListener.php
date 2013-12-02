<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\EventListener;

use Sulu\Component\Workspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * This listener sets the current portal on the portal manager
 * @package Sulu\Bundle\CoreBundle\EventListener
 */
class RequestListener
{
    /**
     * @var \Sulu\Component\Workspace\Analyzer\RequestAnalyzerInterface
     */
    protected $requestAnalyzer;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->requestAnalyzer->analyze($event->getRequest());
    }
}
