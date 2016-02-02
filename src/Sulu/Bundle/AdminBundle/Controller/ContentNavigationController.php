<?php

/*
 * This file is part of Sulu.
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
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationAliasNotFoundException;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistryInterface;
use Sulu\Component\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller returns all the content navigation items for a given alias.
 *
 * @RouteResource("content-navigation")
 */
class ContentNavigationController implements ClassResourceInterface
{
    /**
     * @var ContentNavigationRegistryInterface
     */
    private $contentNavigationRegistry;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    public function __construct(
        ContentNavigationRegistryInterface $contentNavigationRegistry,
        ViewHandlerInterface $viewHandler
    ) {
        $this->contentNavigationRegistry = $contentNavigationRegistry;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Returns all the content navigation items for a given alias.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        try {
            $alias = $request->get('alias');

            if (!$alias) {
                throw new RestException('The alias attribute is required to load the content navigation');
            }

            $options = $request->query->all();

            $contentNavigationItems = $this->contentNavigationRegistry->getNavigationItems($alias, $options);

            $view = View::create($contentNavigationItems);
        } catch (ContentNavigationAliasNotFoundException $exc) {
            $restException = new RestException(
                $exc->getMessage(),
                0,
                $exc
            );
            $view = View::create($restException->toArray(), 404);
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }
}
