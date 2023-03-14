<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Component\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\Request;

@trigger_deprecation(
    'sulu/sulu',
    '2.2',
    'The "%s" class is deprecated, use data from "%s" instead.',
    ContextsController::class,
    AdminController::class
);

/**
 * @deprecated Deprecated since Sulu 2.2, use data from Sulu\Bundle\AdminBundle\Controller\AdminController::configAction
 *
 * @RouteResource("security-contexts")
 */
class ContextsController extends AbstractRestController implements ClassResourceInterface
{
    /**
     * @var AdminPool
     */
    private $adminPool;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        AdminPool $adminPool
    ) {
        parent::__construct($viewHandler);

        $this->adminPool = $adminPool;
    }

    public function cgetAction(Request $request)
    {
        $securityContexts = $this->adminPool->getSecurityContextsWithPlaceholder();
        $view = $this->view($securityContexts);

        return $this->handleView($view);
    }
}
