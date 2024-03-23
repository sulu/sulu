<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Rest\Exception\RestException;
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

        return match ($action) {
            'generate' => $this->generateUrlResponse($request),
            default => throw new RestException('Unrecognized action: ' . $action),
        };
    }

    private function generateUrlResponse(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);

        $resourceLocator = $resourceLocatorStrategy->generate(
            \implode('-', $this->getRequestParameter($request, 'parts', true)),
            $this->getRequestParameter($request, 'parentId'),
            $webspaceKey,
            $this->getRequestParameter($request, 'locale'),
            null,
            $this->getRequestParameter($request, 'id') ?: null
        );

        return $this->viewHandler->handle(View::create(['resourcelocator' => $resourceLocator]));
    }
}
