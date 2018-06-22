<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;

class ResourcelocatorController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    public function __construct(
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        ViewHandler $viewHandler
    ) {
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
        $this->viewHandler = $viewHandler;
    }

    public function postAction(Request $request)
    {
        $action = $request->query->get('action');
        switch ($action) {
            case 'generate':
                return $this->generateUrlResponse($request);
        }

        throw new RestExeption('Unrecognized action: ' . $action);
    }

    private function generateUrlResponse(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);

        $resourceLocator = $resourceLocatorStrategy->generate(
            implode('-', $this->getRequestParameter($request, 'parts', true)),
            $this->getRequestParameter($request, 'parent'),
            $webspaceKey,
            $this->getRequestParameter($request, 'locale')
        );

        return $this->viewHandler->handle(View::create(['resourcelocator' => $resourceLocator]));
    }
}
