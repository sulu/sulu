<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller returns all the content navigation items for a given alias
 * @RouteResource("content-navigation")
 */
class ContentNavigationController implements ClassResourceInterface
{
    /**
     * @var ContentNavigationCollectorInterface
     */
    private $contentNavigationCollector;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    public function __construct(
        ContentNavigationCollectorInterface $contentNavigationCollector,
        ViewHandlerInterface $viewHandler
    ) {
        $this->contentNavigationCollector = $contentNavigationCollector;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Returns all the content navigation items for a given alias
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $alias = $request->get('alias');
        $options = $request->query->all();

        $contentNavigationItems = $this->contentNavigationCollector->getNavigationItems($alias, $options);

        return $this->viewHandler->handle(View::create($contentNavigationItems));
    }
}
